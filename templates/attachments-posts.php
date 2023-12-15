<?php
/**
 * The template for displaying attachments as posts.
 *
 * Override this template by copying it to yourtheme/attachments-posts.php
 *
 * @author Digital Factory
 * @package Download Attachments/Templates
 */

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( empty( $args ) || ! is_array( $args ) )
	exit;

// extract variable storing all the parameters and attachments
extract( $args );

// you can start editing here
?>

<?php if ( ! ( $display_empty === 0 && $count === 0 ) && $container !== '' ) : ?>

	<<?php echo $container . ( $container_id !== '' ? ' id="' . esc_attr( $container_id ) . '"' : '' ) . ( $container_class !== '' ? ' class="' . esc_attr( $container_class ) . '"' : '' ); ?>>

<?php endif; ?>

<?php echo $content_before; // wp_kses'ed ?>

<?php if ( $count > 0 ) :
	$i = 1; ?>

	<ul class="da-attachments-posts">

	<?php
	// attachments loop
	foreach ( $attachments as $attachment ) :
	?>

		<?php if ( $use_desc_for_title === 1 && $attachment['attachment_description'] !== '' ) :
			$attachment_title = apply_filters( 'da_display_attachment_title', $attachment['description'] );
		else :
			$attachment_title = apply_filters( 'da_display_attachment_title', $attachment['title'] );
		endif; ?>

		<li class="<?php echo esc_attr( $attachment['type'] ); ?>">

			<?php if ( $display_index === 1 ) : ?>
				<div class="attachment-index"><?php echo $i++; ?></div>
			<?php endif; ?>

			<?php if ( $display_icon === 1 ) : ?>
				<img class="attachment-icon" src="<?php echo esc_url( $attachment['icon_url'] ); ?>" alt="<?php echo esc_attr( $attachment['type'] ); ?>" />
			<?php endif; ?>

			<?php if ( $link_before !== '' ) : ?>
				<span class="attachment-link-before"><?php echo $link_before; // wp_kses'ed ?></span>
			<?php endif; ?>

			<a href="<?php echo esc_url( da_get_download_attachment_url( $attachment['ID'] ) ); ?>" class="attachment-link" title="<?php echo esc_html( $attachment_title ); ?>"><?php echo esc_html( $attachment_title ) . ( $display_count ? ' <span class="count">(' . esc_html( number_format_i18n( $attachment['downloads'] ) ) . ')</span>' : '' ); ?></a>

			<?php if ( $link_after !== '' ) : ?>
				<span class="attachment-link-after"><?php echo $link_after; // wp_kses'ed ?></span>
			<?php endif; ?>

			<br />

			<?php if ( $display_caption === 1 && $attachment['caption'] !== '' ) : ?>
				<span class="attachment-caption"><?php echo esc_html( $attachment['caption'] ); ?></span><br />
			<?php endif; ?>

			<?php if ( $display_description === 1 && $use_desc_for_title === 0 && $attachment['description'] !== '' ) : ?>
				<span class="attachment-description"><?php echo esc_html( $attachment['description'] ); ?></span><br />
			<?php endif; ?>

			<?php if ( $display_date === 1 ) : ?>
				<div class="attachment-date"><span class="attachment-label"><?php echo esc_html__( 'Date added', 'download-attachments' ); ?>:</span> <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $attachment['date_added'] ) ) ); ?></div>
			<?php endif; ?>

			<?php if ( $display_user === 1 ) : ?>
				<div class="attachment-user"><span class="attachment-label"><?php echo esc_html__( 'Added by', 'download-attachments' ); ?>:</span> <?php echo esc_html( get_the_author_meta( 'display_name', $attachment['user_added'] ) ); ?></div>
			<?php endif; ?>

			<?php if ( $display_size === 1 ) : ?>
				<div class="attachment-size"><span class="attachment-label"><?php echo esc_html__( 'File size', 'download-attachments' ); ?>:</span> <?php echo esc_html( size_format( $attachment['size'] ) ); ?></div>
			<?php endif; ?>

		</li>

	<?php endforeach; ?>

	</ul>

<?php elseif ( $display_empty === 1 ) : ?>

	<?php echo esc_html( $display_option_none ); ?>

<?php endif; ?>

<?php echo $content_after; // wp_kses'ed ?>

<?php if ( ! ( $display_empty === 0 && $count === 0 ) && $container !== '' ) : ?>
	</<?php echo $container; ?>>
<?php endif; ?>