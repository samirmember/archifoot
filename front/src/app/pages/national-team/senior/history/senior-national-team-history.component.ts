import {
  AfterViewInit,
  Component,
  ElementRef,
  OnDestroy,
  QueryList,
  ViewChildren,
  inject,
  signal,
} from '@angular/core';
import { catchError, of } from 'rxjs';
import GLightbox from 'glightbox';
import counterUp from 'counterup2';
import { FixturesStats, ResultService } from 'src/app/services/result.service';

@Component({
  selector: 'app-senior-national-team-history',
  imports: [],
  templateUrl: './senior-national-team-history.component.html',
  styleUrl: './senior-national-team-history.component.scss',
})
export class SeniorNationalTeamHistoryComponent implements AfterViewInit, OnDestroy {
  resultService = inject(ResultService);
  totalMatches = signal<number>(0);
  totalWins = signal<number>(0);
  totalGoals = signal<number>(0);
  trophyWins = signal<number>(0);

  @ViewChildren('counterEl', { read: ElementRef })
  private counterEls!: QueryList<ElementRef<HTMLElement>>;
  private io?: IntersectionObserver;
  private lightbox: any;

  ngAfterViewInit(): void {
    this.lightbox = GLightbox({
      selector: '[data-glightbox]',
    });

    const emptyResponse: FixturesStats = {
      totalMatches: 0,
      totalWins: 0,
      totalGoals: 0,
      trophyWins: 0,
    };
    const subscription = this.resultService
      .buildFixturesStats()
      .pipe(catchError(() => of(emptyResponse)))
      .subscribe((response: FixturesStats) => {
        console.log(response);
        this.totalMatches.set(response.totalMatches);
        this.totalWins.set(response.totalWins);
        this.totalGoals.set(response.totalGoals);
        this.trophyWins.set(response.trophyWins);
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
