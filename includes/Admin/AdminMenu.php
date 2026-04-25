<?php
namespace RobotForooshande\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdminMenu {

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'addMenuPages' ] );
    }

    public function addMenuPages(): void {
        add_menu_page(
            'ربات فروشنده',
            'ربات فروشنده',
            'manage_woocommerce',
            'robot-forooshande',
            [ $this, 'renderSettingsPage' ],
            'dashicons-format-chat',
            56
        );

        add_submenu_page(
            'robot-forooshande',
            'تنظیمات',
            'تنظیمات',
            'manage_woocommerce',
            'robot-forooshande',
            [ $this, 'renderSettingsPage' ]
        );

        add_submenu_page(
            'robot-forooshande',
            'لاگ‌ها',
            'لاگ‌ها',
            'manage_woocommerce',
            'robot-forooshande-logs',
            [ $this, 'renderLogsPage' ]
        );

        add_submenu_page(
            'robot-forooshande',
            'وضعیت سیستم',
            'وضعیت سیستم',
            'manage_woocommerce',
            'robot-forooshande-status',
            [ $this, 'renderStatusPage' ]
        );
    }

    public function renderSettingsPage(): void {
        $settings = new SettingsPage();
        $settings->render();
    }

    public function renderLogsPage(): void {
        include RF_PLUGIN_DIR . 'templates/admin/tab-logs.php';
    }

    public function renderStatusPage(): void {
        include RF_PLUGIN_DIR . 'templates/admin/tab-status.php';
    }
}
