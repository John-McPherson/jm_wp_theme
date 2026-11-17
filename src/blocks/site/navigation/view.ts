export class MainNavigation {
	private readonly menuButton: HTMLButtonElement | null;
	private readonly menuList: HTMLUListElement | null;
	private readonly navigation: HTMLElement | null;
	private readonly desktopQuery = window.matchMedia( '(min-width: 768px)' );

	private isOpen: boolean;

	constructor() {
		this.navigation =
			document.querySelector< HTMLElement >( '.jmc-navigation' );
		this.menuButton = this.navigation?.querySelector< HTMLButtonElement >(
			'.jmc-navigation__toggle'
		);
		this.menuList = this.navigation?.querySelector< HTMLUListElement >(
			'.jmc-navigation__list'
		);

		this.isOpen = false;
	}

	init(): void {
		if ( ! this.menuButton || ! this.menuList ) {
			return;
		}

		this.menuButton.addEventListener( 'click', this.toggleMenu );

		document.addEventListener( 'keydown', this.handleKeydown );

		window.addEventListener( 'resize', this.handleResize );

		this.menuList.hidden = true;
		this.menuButton.setAttribute( 'aria-expanded', String( this.isOpen ) );

		this.handleResize();

		setTimeout( () => {
			this.navigation?.classList.remove( 'no-js' );
		}, 10 );
	}

	private toggleMenu = (): void => {
		if ( ! this.menuButton || ! this.menuList ) {
			return;
		}

		this.isOpen = ! this.isOpen;
		this.menuButton.setAttribute( 'aria-expanded', String( this.isOpen ) );
		this.menuList.hidden = ! this.isOpen;
	};

	private closeMenu = (): void => {
		if ( ! this.menuButton || ! this.menuList ) {
			return;
		}

		this.isOpen = false;

		this.menuButton.setAttribute( 'aria-expanded', String( this.isOpen ) );
		this.menuList.hidden = ! this.isOpen;
		this.menuButton.focus();
	};

	private handleResize = (): void => {
		if ( ! this.menuButton || ! this.menuList ) {
			return;
		}

		if ( this.desktopQuery.matches ) {
			this.isOpen = false;
			this.menuButton.setAttribute( 'aria-expanded', 'false' );
			this.menuList.hidden = false;
			return;
		}

		this.menuList.hidden = ! this.isOpen;
	};
	private handleKeydown = ( event: KeyboardEvent ): void => {
		if ( event.key === 'Escape' && this.isOpen ) {
			this.closeMenu();
		}
	};
}

new MainNavigation().init();
