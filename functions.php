<?php
/**
 * Kadence functions and definitions
 *
 * This file must be parseable by PHP 5.2.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package kadence
 */

define( 'KADENCE_VERSION', '1.2.22' );
define( 'KADENCE_MINIMUM_WP_VERSION', '6.0' );
define( 'KADENCE_MINIMUM_PHP_VERSION', '7.4' );

// Bail if requirements are not met.
if ( version_compare( $GLOBALS['wp_version'], KADENCE_MINIMUM_WP_VERSION, '<' ) || version_compare( phpversion(), KADENCE_MINIMUM_PHP_VERSION, '<' ) ) {
	require get_template_directory() . '/inc/back-compat.php';
	return;
}
// Include WordPress shims.
require get_template_directory() . '/inc/wordpress-shims.php';

// Load the `kadence()` entry point function.
require get_template_directory() . '/inc/class-theme.php';

// Load the `kadence()` entry point function.
require get_template_directory() . '/inc/functions.php';

// Initialize the theme.
call_user_func( 'Kadence\kadence' );

// Buat role jika belum ada
add_action('init', function() {
    if (!get_role('umum')) {
        add_role('umum', 'Umum', ['read' => true]);
    }
    if (!get_role('professional')) {
        add_role('professional', 'Professional', ['read' => true]);
    }
});

// Validasi field Profesi Course
add_filter('tutor_student_register_validation_errors', function($errors) {
    if (empty($_POST['profesi_course'])) {
        $errors[] = __('Silakan pilih Profesi Course.', 'tutor');
    }
    return $errors;
});

// Set role otomatis berdasarkan pilihan
add_action('user_register', function($user_id) {
    if (isset($_POST['profesi_course'])) {
        $selected_role = sanitize_text_field($_POST['profesi_course']);
        if (in_array($selected_role, ['umum', 'professional'])) {
            $user = new WP_User($user_id);
            $user->set_role($selected_role);
        }
    }
});

add_action( 'wp_ajax_upload_verification_pdf', 'upload_verification_pdf_handler' );

function upload_verification_pdf_handler() {
    check_ajax_referer( 'upload_verification_pdf' );

    if ( empty($_FILES['verification_pdf']) || $_FILES['verification_pdf']['type'] !== 'application/pdf' ) {
        wp_send_json_error( 'File harus PDF.' );
    }

    $file = $_FILES['verification_pdf'];

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $upload = wp_handle_upload( $file, array( 'test_form' => false ) );

    if ( isset($upload['error']) ) {
        wp_send_json_error( $upload['error'] );
    }

    $attachment_id = wp_insert_attachment( array(
        'post_mime_type' => $upload['type'],
        'post_title'     => sanitize_file_name( $file['name'] ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    ), $upload['file'] );

    if ( ! is_wp_error( $attachment_id ) ) {
        wp_send_json_success( array( 'attachment_id' => $attachment_id ) );
    } else {
        wp_send_json_error( 'Upload gagal.' );
    }
}
