import { DOCUMENT } from '@angular/common';
import { Component, HostListener, OnDestroy, Renderer2, inject } from '@angular/core';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-header',
  imports: [RouterModule],
  templateUrl: './header.component.html',
  styleUrl: './header.component.scss',
})
export class HeaderComponent implements OnDestroy {
  private readonly renderer = inject(Renderer2);
  private readonly document = inject(DOCUMENT);

  isMobileMenuOpen = false;

  toggleMobileMenu(): void {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
    this.syncBodyScrollLock();
  }

  closeMobileMenu(): void {
    if (!this.isMobileMenuOpen) {
      return;
    }

    this.isMobileMenuOpen = false;
    this.syncBodyScrollLock();
  }

  onOffcanvasClick(event: MouseEvent): void {
    const target = event.target as HTMLElement | null;
    const clickedLink = target?.closest('a');

    if (clickedLink == null || clickedLink.classList.contains('dropdown-toggle')) {
      return;
    }

    this.closeMobileMenu();
  }

  @HostListener('document:keydown.escape')
  onEscape(): void {
    this.closeMobileMenu();
  }

  @HostListener('window:resize')
  onWindowResize(): void {
    if (window.innerWidth >= 992) {
      this.closeMobileMenu();
    }
  }

  ngOnDestroy(): void {
    this.renderer.removeClass(this.document.body, 'mobile-menu-open');
  }

  private syncBodyScrollLock(): void {
    if (this.isMobileMenuOpen) {
      this.renderer.addClass(this.document.body, 'mobile-menu-open');
      return;
    }

    this.renderer.removeClass(this.document.body, 'mobile-menu-open');
  }
}
