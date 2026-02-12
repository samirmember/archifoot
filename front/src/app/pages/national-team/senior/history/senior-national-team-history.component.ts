import {
  AfterViewInit,
  Component,
  ElementRef,
  OnDestroy,
  QueryList,
  ViewChildren,
} from '@angular/core';
import GLightbox from 'glightbox';
import counterUp from 'counterup2';

@Component({
  selector: 'app-senior-national-team-history',
  imports: [],
  templateUrl: './senior-national-team-history.component.html',
  styleUrl: './senior-national-team-history.component.scss',
})
export class SeniorNationalTeamHistoryComponent implements AfterViewInit, OnDestroy {
  totalMatches = 768;
  totalWins = 400;
  totalGoals = 360;
  trophyWins = 3;

  @ViewChildren('counterEl', { read: ElementRef })
  private counterEls!: QueryList<ElementRef<HTMLElement>>;
  private io?: IntersectionObserver;
  private lightbox: any;

  ngAfterViewInit(): void {
    this.lightbox = GLightbox({
      selector: '[data-glightbox]',
    });

    // Déclenchement au moment où l'élément devient visible (pattern du README) :contentReference[oaicite:4]{index=4}
    this.io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;

          const el = entry.target as HTMLElement;

          counterUp(el, {
            duration: 1200,
            delay: 16,
          });

          // Une seule fois par compteur
          this.io?.unobserve(el);
        });
      },
      {
        threshold: 0.6, // ajuste selon ton UI
      },
    );

    // Observe tous les compteurs
    this.counterEls.forEach((ref) => this.io!.observe(ref.nativeElement));
  }

  ngOnDestroy(): void {
    this.lightbox?.destroy();
    this.io?.disconnect();
  }
}
