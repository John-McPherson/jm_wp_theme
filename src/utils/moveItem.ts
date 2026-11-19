/**
 * Move a list item by an offset, returning a new list.
 *
 * Moves that would leave the list are ignored and return the list unchanged.
 *
 * @param items  List items.
 * @param index  Index of the item to move.
 * @param offset Positions to move by; negative moves towards the start.
 *
 * @return The reordered list.
 */
export default function moveItem< Item >(
	items: readonly Item[],
	index: number,
	offset: number
): Item[] {
	const target = index + offset;

	if (
		index < 0 ||
		index >= items.length ||
		target < 0 ||
		target >= items.length
	) {
		return [ ...items ];
	}

	const next = [ ...items ];
	const [ item ] = next.splice( index, 1 );

	next.splice( target, 0, item );

	return next;
}
