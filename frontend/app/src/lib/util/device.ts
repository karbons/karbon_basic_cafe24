import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';
import { Device } from '@capacitor/device';
import { fcmToken, deviceModel, osVersion } from '$lib/store/device';

export async function initDevice() {
    if (!Capacitor.isNativePlatform()) return;

    // Device Info
    try {
        const info = await Device.getInfo();
        deviceModel.set(info.model);
        osVersion.set(`${info.operatingSystem} ${info.osVersion}`);
    } catch (e) {
        console.error('Failed to get device info', e);
    }

    // Push Notifications
    try {
        let perm = await PushNotifications.checkPermissions();
        if (perm.receive === 'prompt') {
            perm = await PushNotifications.requestPermissions();
        }
        if (perm.receive === 'granted') {
            await PushNotifications.register();
        }

        PushNotifications.addListener('registration', (token) => {
            fcmToken.set(token.value);
            console.log('Push Registration Token: ', token.value);
        });

        PushNotifications.addListener('registrationError', (error) => {
            console.error('Push Registration Error: ', error);
        });

        // Notification Listeners
        PushNotifications.addListener('pushNotificationReceived', (notification) => {
            console.log('Push received: ', notification);
        });

        PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
            console.log('Push action performed: ', notification);
        });

    } catch (e) {
        console.error('Failed to init push', e);
    }
}
