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
		<<?php echo $container . ( $container_id !== '' ? ' id="' . $container_id . '"' : '' ) . ( $container_class !== '' ? ' class="' . $container_class . '"' : '' ); ?>>
	<?php endif; ?>

	<?php if ( $title !== '' ) : ?>
		<?php echo ( $title !== '' ? '<' . $title_container . ' class="' . $title_class . '">' . $title . '</' . $title_container . '>' : '' ); ?>
	<?php endif; ?>

<?php endif; ?>

<?php echo $content_before; ?>

<?php if ( $count > 0 ) :
	$i = 1;	?>

	<table class="da-attachments-dynatable">

		<thead>

			<?php if ( $display_index === 1 ) : ?>
				<th class="attachment-index">#</th>
			<?php endif; ?>

			<th class="attachment-title"><?php echo __( 'File', 'download-attachments' ); ?></th>

			<?php if ( $display_caption === 1 || ( $display_description === 1 && $use_desc_for_title === 0 ) ) : ?>
				<th class="attachment-about"><?php echo __( 'Description', 'download-attachments' ); ?></th>
			<?php endif; ?>

			<?php if ( $display_date === 1 ) : ?>
				<th class="attachment-date"><?php echo __( 'Date added', 'download-attachments' ); ?></th>
			<?php endif; ?>

			<?php if ( $display_user === 1 ) : ?>
				<th class="attachment-user"><?php echo __( 'Added by', 'download-attachments' ); ?></th>
			<?php endif; ?>

			<?php if ( $display_size === 1 ) : ?>
				<th class="attachment-size" data-dynatable-sorts="size" data-dynatable-column="file_size"><?php echo __( 'File size', 'download-attachments' ); ?></th>
			<?php endif; ?>

			<?php if ( $display_count === 1 ) : ?>
				<th class="attachment-downloads"><?php echo __( 'Downloads', 'download-attachments' ); ?></th>
			<?php endif; ?>

		</thead>

		<tbody>

		<?php 
		// attachments loop
		foreach ( $attachments as $attachment ) : 
		?>

			<?php
			if ( $use_desc_for_title === 1 && $attachment_description !== '' ) :
				$attachment_title = apply_filters( 'da_display_attachment_title', $attachment['description'] );
			else :
				$attachment_title = apply_filters( 'da_display_attachment_title', $attachment['title'] );
			endif;
			?>

			<tr class="<?php echo $attachment['type']; ?>">

				<?php if ( $display_index === 1 ) : ?>
					<td class="attachment-index"><?php echo $i ++; ?></td> 
				<?php endif; ?>

				<td class="attachment-title">

					<?php if ( $display_icon === 1 ) : ?>
						<img class="attachment-icon" src="<?php echo $attachment['icon_url']; ?>" alt="<?php echo $attachment['type']; ?>" /> 
					<?php endif; ?>

					<?php if ( $link_before !== '' ) : ?>
						<span class="attachment-link-before"><?php echo $link_before; ?></span>
					<?php endif; ?>

					<a href="<?php echo da_get_download_attachment_url( $attachment['ID'] ); ?>" class="attachment-link" title="<?php echo esc_html( $attachment_title ); ?>"><?php echo $attachment_title; ?></a>

					<?php if ( $link_after !== '' ) : ?>
						<span class="attachment-link-after"><?php echo $link_after; ?></span>
					<?php endif; ?>

				</td>

				<?php if ( $display_caption === 1 || ( $display_description === 1 && $use_desc_for_title === 0 ) ) : ?>
					<td class="attachment-about">
				<?php endif; ?>

				<?php if ( $display_caption === 1 && $attachment['caption'] !== '' ) : ?>
					<span class="attachment-caption"><?php echo $attachment['caption']; ?></span><br />
				<?php endif; ?>

				<?php if ( $display_description === 1 && $use_desc_for_title === 0 && $attachment['description'] !== '' ) : ?>
					<span class="attachment-description"><?php echo $attachment['description']; ?></span><br />
				<?php endif; ?>

				<?php if ( $display_caption === 1 || ( $display_description === 1 && $use_desc_for_title === 0 ) ) : ?>
					</td>
				<?php endif; ?>

				<?php if ( $display_date === 1 ) : ?>
					<td class="attachment-date"><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $attachment['date_added'] ) ); ?></td>
				<?php endif; ?>

				<?php if ( $display_user === 1 ) : ?>
					<td class="attachment-user"><?php echo get_the_author_meta( 'display_name', $attachment['user_added'] ); ?></td>
				<?php endif; ?>

				<?php if ( $display_size === 1 ) : ?>
					<td class="attachment-size" data-size="<?php echo $attachment['size']; ?>"><?php echo size_format( $attachment['size'] ); ?></td>
				<?php endif; ?>

				<?php if ( $display_count === 1 ) : ?>
					<td class="attachment-downloads"><?php echo $attachment['downloads']; ?></td>
				<?php endif; ?>

			</tr>

		<?php endforeach; ?>

		</tbody>

	</table>

<?php elseif ( $display_empty === 1 ) : ?>
		
	<?php echo $display_option_none; ?>
		
<?php endif; ?>

<?php echo $content_after; ?>

<?php if ( ! ( $display_empty === 0 && $count === 0 ) && $container !== '' ) : ?>
	</<?php echo $container; ?>>
<?php endif; ?>