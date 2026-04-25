<?php if ( ! defined( 'ABSPATH' ) ) exit;
$page   = max( 1, (int) ( $_GET['log_page'] ?? 1 ) );
$level  = sanitize_key( $_GET['log_level'] ?? '' );
$result = \RobotForooshande\Helpers\Logger::getLogs( $page, 50, $level );
$logs   = $result['items'];
$total  = $result['total'];
$pages  = max( 1, (int) $result['pages'] );
?>
<div class="rf-settings-page" dir="rtl">
    <h2>📋 لاگ‌ها</h2>

    <div style="margin-bottom:15px;">
        <form method="get" style="display:inline-flex;gap:10px;align-items:center;">
            <input type="hidden" name="page" value="robot-forooshande-logs">
            <select name="log_level">
                <option value="">همه</option>
                <option value="debug" <?php selected( $level, 'debug' ); ?>>Debug</option>
                <option value="info" <?php selected( $level, 'info' ); ?>>Info</option>
                <option value="warning" <?php selected( $level, 'warning' ); ?>>Warning</option>
                <option value="error" <?php selected( $level, 'error' ); ?>>Error</option>
            </select>
            <button type="submit" class="button">فیلتر</button>
        </form>
        <button type="button" class="button" id="rf-clear-logs" style="margin-right:10px;">پاکسازی</button>
    </div>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>تاریخ</th>
                <th>سطح</th>
                <th>پیام</th>
                <th>جزئیات</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $logs ) ) : ?>
                <tr><td colspan="4" style="text-align:center;">لاگی یافت نشد.</td></tr>
            <?php else : ?>
                <?php foreach ( $logs as $log ) : ?>
                    <tr>
                        <td style="white-space:nowrap;"><?php echo esc_html( $log->created_at ); ?></td>
                        <td>
                            <?php
                            $badge = match ( $log->level ) {
                                'error'   => '🔴',
                                'warning' => '🟡',
                                'info'    => '🔵',
                                default   => '⚪',
                            };
                            echo "{$badge} " . esc_html( $log->level );
                            ?>
                        </td>
                        <td><?php echo esc_html( $log->message ); ?></td>
                        <td><code style="font-size:11px;"><?php echo esc_html( mb_substr( $log->context ?? '', 0, 200 ) ); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $pages > 1 ) : ?>
        <div class="tablenav" style="margin-top:10px;">
            <div class="tablenav-pages">
                <?php for ( $i = 1; $i <= $pages; $i++ ) : ?>
                    <?php if ( $i === $page ) : ?>
                        <strong><?php echo esc_html( $i ); ?></strong>
                    <?php else : ?>
                        <a href="<?php echo esc_url( add_query_arg( [ 'log_page' => $i, 'log_level' => $level ], admin_url( 'admin.php?page=robot-forooshande-logs' ) ) ); ?>">
                            <?php echo esc_html( $i ); ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
