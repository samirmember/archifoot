import { AfterViewInit, Component, OnDestroy } from '@angular/core';
import GLightbox from 'glightbox';

@Component({
  selector: 'app-senior-national-team-history',
  imports: [],
  templateUrl: './senior-national-team-history.component.html',
  styleUrl: './senior-national-team-history.component.scss',
})
export class SeniorNationalTeamHistoryComponent implements AfterViewInit, OnDestroy {
  private lightbox: any;

  ngAfterViewInit(): void {
    this.lightbox = GLightbox({
      selector: '[data-glightbox]',
    });
  }

  ngOnDestroy(): void {
    this.lightbox?.destroy();
  }
}
