<?php
/**
 * Render a list of navigation links.
 *
 * The caller supplies the BEM block class, so the list, items and links get
 * `<block>__list`, `<block>__item` and `<block>__link`. The link to the
 * current page is marked with `aria-current="page"`. Pass `labelled_by` with
 * a heading's ID to name the list after it.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Component arguments for the link-list template.
 *
 * @var array{
 *     links?: array<int, array{id:int,title:string,url:string}>,
 *     block_class?: string,
 *     labelled_by?: string
 * } $args
 */

$args ??= [];

$links       = is_array( $args['links'] ?? null ) ? $args['links'] : [];
$block_class = jmc_args_string(
	args: $args,
	key: 'block_class'
);

if ( [] === $links || '' === $block_class ) {
	return;
}

$list_attributes = [
	'class'           => $block_class . '__list',
	'aria-labelledby' => jmc_args_string( args: $args, key: 'labelled_by' ),
];
?>
<ul<?php jmc_the_attributes( $list_attributes ); ?>>
	<?php
	foreach ( $links as $link_item ) :
		$link_attributes = [
			'href'  => $link_item['url'],
			'class' => $block_class . '__link',
		];

		// is_page( 0 ) matches every page, so custom links (no ID) must be skipped.
		if ( 0 < $link_item['id'] && is_page( $link_item['id'] ) ) {
			$link_attributes['aria-current'] = 'page';
		}
		?>
		<li class="<?php echo esc_attr( $block_class . '__item' ); ?>">
			<a<?php jmc_the_attributes( $link_attributes ); ?>><?php echo esc_html( $link_item['title'] ); ?></a>
		</li>
	<?php endforeach; ?>
</ul>
