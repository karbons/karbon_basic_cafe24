import { apiGet } from '$lib/api/api';
import { Capacitor } from '@capacitor/core';
import { App } from '@capacitor/app';
import { Browser } from '@capacitor/browser';

/**
 * 앱 업데이트 정보 타입
 */
export interface AppVersionInfo {
	current_version: string;
	min_version: string;
	update_url: {
		ios: string;
		android: string;
	};
}

/**
 * 앱 업데이트 체크 결과 타입
 */
export interface UpdateCheckResult {
	needsUpdate: boolean; // 업데이트 가능 여부
	isForced: boolean; // 강제 업데이트 여부 (min_version 미충족)
	currentVersion: string; // 현재 앱 버전
	latestVersion: string; // 최신 앱 버전
	updateUrl: string; // 스토어 URL
}

/**
 * 앱 버전을 비교하는 헬퍼 함수
 * @param version1 비교할 버전 1 (예: "1.2.3")
 * @param version2 비교할 버전 2 (예: "1.2.4")
 * @returns version1 < version2이면 -1, 같으면 0, version1 > version2이면 1
 */
function compareVersions(version1: string, version2: string): number {
	const v1Parts = version1.split('.').map(Number);
	const v2Parts = version2.split('.').map(Number);

	for (let i = 0; i < Math.max(v1Parts.length, v2Parts.length); i++) {
		const v1 = v1Parts[i] || 0;
		const v2 = v2Parts[i] || 0;

		if (v1 < v2) return -1;
		if (v1 > v2) return 1;
	}

	return 0;
}

/**
 * 앱 업데이트 확인
 * API에서 최신 버전 정보를 조회하고 현재 버전과 비교합니다.
 * @returns 업데이트 체크 결과
 */
export async function checkAppUpdate(): Promise<UpdateCheckResult> {
	try {
		// 네이티브 플랫폼이 아니면 업데이트 체크 스킵
		if (!Capacitor.isNativePlatform()) {
			return {
				needsUpdate: false,
				isForced: false,
				currentVersion: '0.0.0',
				latestVersion: '0.0.0',
				updateUrl: ''
			};
		}

		// 현재 앱 버전 조회
		const appInfo = await App.getInfo();
		const currentVersion = appInfo.version;

		// API에서 최신 버전 정보 조회
		const versionInfo = await apiGet<AppVersionInfo>('/app/version');

		const latestVersion = versionInfo.current_version;
		const minVersion = versionInfo.min_version;

		// 현재 플랫폼 확인
		const platform = Capacitor.getPlatform();
		const updateUrl =
			platform === 'ios' ? versionInfo.update_url.ios : versionInfo.update_url.android;

		// 버전 비교
		const isOutdated = compareVersions(currentVersion, latestVersion) < 0;
		const isBelowMinVersion = compareVersions(currentVersion, minVersion) < 0;

		return {
			needsUpdate: isOutdated,
			isForced: isBelowMinVersion,
			currentVersion,
			latestVersion,
			updateUrl
		};
	} catch (error) {
		console.error('앱 업데이트 확인 실패:', error);
		throw error;
	}
}

/**
 * 앱 업데이트 프롬프트 표시
 * 업데이트 필요 여부에 따라 알림 또는 확인 대화상자를 표시합니다.
 * @param updateInfo 업데이트 체크 결과
 */
export async function showUpdatePrompt(updateInfo: UpdateCheckResult): Promise<void> {
	// 업데이트가 필요 없으면 반환
	if (!updateInfo.needsUpdate) {
		return;
	}

	try {
		if (updateInfo.isForced) {
			// 강제 업데이트: 확인 버튼만 제공
			const confirmed = await showAlert(
				'필수 업데이트',
				`앱을 최신 버전으로 업데이트해야 합니다.\n현재 버전: ${updateInfo.currentVersion}\n최신 버전: ${updateInfo.latestVersion}`,
				['업데이트']
			);

			if (confirmed) {
				await openStoreUrl(updateInfo.updateUrl);
			}
		} else {
			// 선택적 업데이트: 나중에 버튼 제공
			const confirmed = await showConfirm(
				'앱 업데이트 가능',
				`새로운 버전이 출시되었습니다.\n현재 버전: ${updateInfo.currentVersion}\n최신 버전: ${updateInfo.latestVersion}\n\n지금 업데이트하시겠습니까?`
			);

			if (confirmed) {
				await openStoreUrl(updateInfo.updateUrl);
			}
		}
	} catch (error) {
		console.error('업데이트 프롬프트 표시 실패:', error);
		throw error;
	}
}

/**
 * 알림 대화상자 표시 (확인 버튼만)
 * @param title 제목
 * @param message 메시지
 * @param buttons 버튼 텍스트 배열
 * @returns 확인 여부
 */
async function showAlert(title: string, message: string, buttons: string[]): Promise<boolean> {
	if (typeof window !== 'undefined' && window.confirm) {
		return window.confirm(`${title}\n\n${message}`);
	}
	return false;
}

/**
 * 확인 대화상자 표시 (확인/취소 버튼)
 * @param title 제목
 * @param message 메시지
 * @returns 확인 여부
 */
async function showConfirm(title: string, message: string): Promise<boolean> {
	if (typeof window !== 'undefined' && window.confirm) {
		return window.confirm(`${title}\n\n${message}`);
	}
	return false;
}

/**
 * 스토어 URL 열기
 * @param url 스토어 URL
 */
async function openStoreUrl(url: string): Promise<void> {
	try {
		if (Capacitor.isNativePlatform()) {
			// 네이티브 환경에서는 Browser 플러그인 사용
			await Browser.open({ url });
		} else {
			// 웹 환경에서는 새 탭으로 열기
			if (typeof window !== 'undefined') {
				window.open(url, '_blank');
			}
		}
	} catch (error) {
		console.error('스토어 URL 열기 실패:', error);
		throw error;
	}
}
