class MainNavigation {
	private readonly menuButton: HTMLButtonElement | null;
	private readonly menuList: HTMLUListElement | null;
	private isOpen: boolean;

	constructor() {
		this.menuButton = document.querySelector< HTMLButtonElement >(
			'.jmc-navigation__toggle'
		);
		this.menuList = document.querySelector< HTMLUListElement >(
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
	}

	private toggleMenu = (): void => {
		if ( ! this.menuButton || ! this.menuList ) {
			return;
		}

		this.isOpen = ! this.isOpen;
		this.menuButton.setAttribute( 'aria-expanded', String( this.isOpen ) );
	};

	private closeMenu = (): void => {
		if ( ! this.menuButton || ! this.menuList ) {
			return;
		}

		this.isOpen = false;

		this.menuButton.setAttribute( 'aria-expanded', String( this.isOpen ) );
		this.menuButton.focus();
	};

	private handleKeydown = ( event: KeyboardEvent ): void => {
		if ( event.key === 'Escape' ) {
			this.closeMenu();
		}
	};
}

new MainNavigation().init();
