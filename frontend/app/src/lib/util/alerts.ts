/**
 * 알림/컨펌/토스트 사용 예시
 * 
 * import { alertStore, confirmStore, toastStore } from '$lib/store';
 * 
 * // 알림 (제목 + 메시지)
 * alertStore.show({
 *   type: 'success',
 *   title: '성공',
 *   message: '작업이 완료되었습니다.',
 *   duration: 5000 // 5초 후 자동 닫기
 * });
 * 
 * // 컨펌 (확인/취소 다이얼로그)
 * const confirmed = await confirmStore.show({
 *   title: '삭제 확인',
 *   message: '정말 삭제하시겠습니까?',
 *   type: 'danger',
 *   confirmText: '삭제',
 *   cancelText: '취소'
 * });
 * 
 * if (confirmed) {
 *   // 삭제 로직
 * }
 * 
 * // 토스트 (간단한 메시지)
 * toastStore.success('저장되었습니다.');
 * toastStore.error('오류가 발생했습니다.');
 * toastStore.info('정보 메시지');
 * toastStore.warning('경고 메시지');
 */

export { };
