<?php
/**
 * The template for displaying attachments as table.
 *
 * Override this template by copying it to yourtheme/attachments-table.php
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

<?php if ( ! ( $display_empty === 0 && $count === 0 ) ) : ?>

	<?php if ( $container !== '' ) : ?>
		<<?php echo $container . ( $container_id !== '' ? ' id="' . esc_attr( $container_id ) . '"' : '' ) . ( $container_class !== '' ? ' class="' . esc_attr( $container_class ) . '"' : '' ); ?>>
	<?php endif; ?>

	<?php if ( $title !== '' ) : ?>
		<?php echo ( $title !== '' ? '<' . $title_container . ' class="' . esc_attr( $title_class ) . '">' . esc_html( $title ) . '</' . $title_container . '>' : '' ); ?>
	<?php endif; ?>

<?php endif; ?>

<?php echo $content_before; // wp_kses'ed ?>

<?php if ( $count > 0 ) :
	$i = 1;	?>

	<table class="da-attachments-table">

		<thead>

			<?php if ( $display_index === 1 ) : ?>
				<th class="attachment-index">#</th>
			<?php endif; ?>

			<th class="attachment-title"><?php echo esc_html__( 'File', 'download-attachments' ); ?></th>

			<?php if ( $display_caption === 1 || ( $display_description === 1 && $use_desc_for_title === 0 ) ) : ?>
				<th class="attachment-about"><?php echo esc_html__( 'Description', 'download-attachments' ); ?></th>
			<?php endif; ?>

			<?php if ( $display_date === 1 ) : ?>
				<th class="attachment-date"><?php echo esc_html__( 'Date added', 'download-attachments' ); ?></th>
			<?php endif; ?>

			<?php if ( $display_user === 1 ) : ?>
				<th class="attachment-user"><?php echo esc_html__( 'Added by', 'download-attachments' ); ?></th>
			<?php endif; ?>

			<?php if ( $display_size === 1 ) : ?>
				<th class="attachment-size"><?php echo esc_html__( 'File size', 'download-attachments' ); ?></th>
			<?php endif; ?>

			<?php if ( $display_count === 1 ) : ?>
				<th class="attachment-downloads"><?php echo esc_html__( 'Downloads', 'download-attachments' ); ?></th>
			<?php endif; ?>

		</thead>

		<tbody>

		<?php
		// attachments loop
		foreach ( $attachments as $attachment ) :
		?>

			<?php
			if ( $use_desc_for_title === 1 && $attachment['attachment_description'] !== '' ) :
				$attachment_title = apply_filters( 'da_display_attachment_title', $attachment['description'] );
			else :
				$attachment_title = apply_filters( 'da_display_attachment_title', $attachment['title'] );
			endif;
			?>

			<tr class="<?php echo esc_attr( $attachment['type'] ); ?>">

				<?php if ( $display_index === 1 ) : ?>
					<td class="attachment-index"><?php echo $i++; ?></td>
				<?php endif; ?>

				<td class="attachment-title">

					<?php if ( $display_icon === 1 ) : ?>
						<img class="attachment-icon" src="<?php echo esc_url( $attachment['icon_url'] ); ?>" alt="<?php echo esc_attr( $attachment['type'] ); ?>" />
					<?php endif; ?>

					<?php if ( $link_before !== '' ) : ?>
						<span class="attachment-link-before"><?php echo $link_before; // wp_kses'ed ?></span>
					<?php endif; ?>

					<?php da_download_attachment_link( $attachment['ID'], true, array( 'title' => $attachment_title, 'class' => 'attachment-link' ) ); ?>

					<?php if ( $link_after !== '' ) : ?>
						<span class="attachment-link-after"><?php echo $link_after; // wp_kses'ed ?></span>
					<?php endif; ?>

				</td>

				<?php if ( $display_caption === 1 || ( $display_description === 1 && $use_desc_for_title === 0 ) ) : ?>
					<td class="attachment-about">
				<?php endif; ?>

				<?php if ( $display_caption === 1 && $attachment['caption'] !== '' ) : ?>
					<span class="attachment-caption"><?php echo esc_html( $attachment['caption'] ); ?></span><br />
				<?php endif; ?>

				<?php if ( $display_description === 1 && $use_desc_for_title === 0 && $attachment['description'] !== '' ) : ?>
					<span class="attachment-description"><?php echo esc_html( $attachment['description'] ); ?></span><br />
				<?php endif; ?>

				<?php if ( $display_caption === 1 || ( $display_description === 1 && $use_desc_for_title === 0 ) ) : ?>
					</td>
				<?php endif; ?>

				<?php if ( $display_date === 1 ) : ?>
					<td class="attachment-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $attachment['date_added'] ) ) ); ?></td>
				<?php endif; ?>

				<?php if ( $display_user === 1 ) : ?>
					<td class="attachment-user"><?php echo esc_html( get_the_author_meta( 'display_name', $attachment['user_added'] ) ); ?></td>
				<?php endif; ?>

				<?php if ( $display_size === 1 ) : ?>
					<td class="attachment-size"><?php echo esc_html( size_format( $attachment['size'] ) ); ?></td>
				<?php endif; ?>

				<?php if ( $display_count === 1 ) : ?>
					<td class="attachment-downloads"><?php echo (int) $attachment['downloads']; ?></td>
				<?php endif; ?>

			</tr>

		<?php endforeach; ?>

		</tbody>

	</table>

<?php elseif ( $display_empty === 1 ) : ?>

	<?php echo esc_html( $display_option_none ); ?>

<?php endif; ?>

<?php echo $content_after; // wp_kses'ed ?>

<?php if ( ! ( $display_empty === 0 && $count === 0 ) && $container !== '' ) : ?>
	</<?php echo $container; ?>>
<?php endif; ?>