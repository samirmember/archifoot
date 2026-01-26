import { Component, OnDestroy } from '@angular/core';
import GLightbox from 'glightbox';

@Component({
  selector: 'app-senior-national-team-history',
  imports: [],
  templateUrl: './senior-national-team-history.component.html',
  styleUrl: './senior-national-team-history.component.scss',
})
export class SeniorNationalTeamHistoryComponent implements OnDestroy {
  private lightbox: any;

  ngAfterViewInit(): void {
    // Delay to ensure DOM is fully rendered after Angular routing
    setTimeout(() => {
      this.lightbox = GLightbox({
        selector: '[data-glightbox]',
      });
    }, 400);
  }

  ngOnDestroy(): void {
    // Clean up GLightbox instance to prevent memory leaks
    if (this.lightbox) {
      this.lightbox.destroy();
    }
  }
}
