type Submenu = {
	item: HTMLLIElement;
	toggle: HTMLButtonElement;
	list: HTMLUListElement;
};

/**
 * Behaviour for the navigation's first-level submenus.
 *
 * Desktop: each submenu is a dropdown. Its toggle button opens and closes it
 * (click, Enter or Space), and hovering the parent item with a mouse also
 * opens it, as an enhancement rather than a replacement. Only one is open at
 * a time.
 *
 * Mobile: every submenu is expanded inside the primary menu and the toggles
 * are hidden by CSS, so there is nothing to operate.
 */
export class NavigationSubmenus {
	/** Delay before a hover-opened submenu closes, so the pointer can cross gaps. */
	static readonly hoverCloseDelay = 300;

	private readonly submenus: Submenu[] = [];
	private readonly desktopQuery: MediaQueryList;

	private isDesktop: boolean | null = null;
	private open: Submenu | null = null;
	private openedByHover = false;
	private closeTimer: ReturnType< typeof setTimeout > | undefined;

	constructor( navigation: HTMLElement, desktopQuery: MediaQueryList ) {
		this.desktopQuery = desktopQuery;

		navigation
			.querySelectorAll< HTMLButtonElement >(
				'.jmc-navigation__submenu-toggle'
			)
			.forEach( ( toggle ) => {
				const id = toggle.getAttribute( 'aria-controls' );
				const list = id
					? navigation.ownerDocument.getElementById( id )
					: null;
				const item = toggle.closest( 'li' );

				if ( list instanceof HTMLUListElement && item ) {
					this.submenus.push( { item, toggle, list } );
				}
			} );
	}

	init(): void {
		if ( this.submenus.length === 0 ) {
			return;
		}

		this.submenus.forEach( ( submenu ) => {
			submenu.toggle.addEventListener( 'click', () =>
				this.handleToggle( submenu )
			);
			submenu.item.addEventListener( 'pointerenter', ( event ) =>
				this.handlePointerEnter( event, submenu )
			);
			submenu.item.addEventListener( 'pointerleave', ( event ) =>
				this.handlePointerLeave( event, submenu )
			);
			submenu.item.addEventListener( 'focusout', ( event ) =>
				this.handleFocusOut( event, submenu )
			);
		} );

		document.addEventListener( 'click', this.handleDocumentClick );

		this.sync();
	}

	/**
	 * Reset every submenu when the layout crosses the desktop breakpoint:
	 * closed dropdowns on desktop, expanded lists on mobile.
	 */
	sync(): void {
		const isDesktop = this.desktopQuery.matches;

		if ( isDesktop === this.isDesktop ) {
			return;
		}

		this.isDesktop = isDesktop;
		this.clearCloseTimer();
		this.open = null;
		this.openedByHover = false;

		this.submenus.forEach( ( submenu ) =>
			this.setExpanded( submenu, ! isDesktop )
		);
	}

	/**
	 * Close the open desktop submenu in response to Escape.
	 *
	 * @return Whether a submenu was open and has been closed.
	 */
	closeOnEscape(): boolean {
		const submenu = this.open;

		if ( ! submenu ) {
			return false;
		}

		const hadFocus = submenu.item.contains(
			submenu.item.ownerDocument.activeElement
		);

		this.close();

		if ( hadFocus ) {
			submenu.toggle.focus();
		}

		return true;
	}

	/**
	 * Close whichever desktop submenu is open, without moving focus.
	 */
	close = (): void => {
		this.clearCloseTimer();

		if ( this.open ) {
			this.setExpanded( this.open, false );
		}

		this.open = null;
		this.openedByHover = false;
	};

	private openSubmenu( submenu: Submenu, byHover: boolean ): void {
		this.clearCloseTimer();

		if ( this.open !== submenu ) {
			this.close();
			this.setExpanded( submenu, true );
			this.open = submenu;
		}

		this.openedByHover = byHover;
	}

	private handleToggle( submenu: Submenu ): void {
		if ( ! this.desktopQuery.matches ) {
			return;
		}

		if ( this.open === submenu ) {
			this.close();
			return;
		}

		this.openSubmenu( submenu, false );
	}

	private handlePointerEnter( event: Event, submenu: Submenu ): void {
		if ( ! this.isDesktopMouse( event ) ) {
			return;
		}

		if ( this.open === submenu ) {
			this.clearCloseTimer();
			return;
		}

		this.openSubmenu( submenu, true );
	}

	private handlePointerLeave( event: Event, submenu: Submenu ): void {
		if (
			! this.isDesktopMouse( event ) ||
			this.open !== submenu ||
			! this.openedByHover
		) {
			return;
		}

		this.clearCloseTimer();
		this.closeTimer = setTimeout(
			this.close,
			NavigationSubmenus.hoverCloseDelay
		);
	}

	private handleFocusOut( event: FocusEvent, submenu: Submenu ): void {
		const next = event.relatedTarget;

		if (
			this.open !== submenu ||
			( next instanceof Node && submenu.item.contains( next ) )
		) {
			return;
		}

		this.close();
	}

	private handleDocumentClick = ( event: MouseEvent ): void => {
		const target = event.target;

		if (
			! this.open ||
			( target instanceof Node && this.open.item.contains( target ) )
		) {
			return;
		}

		this.close();
	};

	/**
	 * Only a real mouse on desktop triggers hover, so a touch tap (which also
	 * fires pointer events) can't open a submenu that its click then closes.
	 *
	 * @param event Pointer event.
	 */
	private isDesktopMouse( event: Event ): boolean {
		return (
			this.desktopQuery.matches &&
			( event as PointerEvent ).pointerType === 'mouse'
		);
	}

	private clearCloseTimer(): void {
		clearTimeout( this.closeTimer );
		this.closeTimer = undefined;
	}

	private setExpanded( submenu: Submenu, expanded: boolean ): void {
		submenu.toggle.setAttribute( 'aria-expanded', String( expanded ) );
		submenu.list.hidden = ! expanded;
	}
}
