import {
  AfterViewInit,
  Component,
  ElementRef,
  OnDestroy,
  OnInit,
  QueryList,
  ViewChildren,
  inject,
  signal,
} from '@angular/core';
import { Subscription, catchError, of } from 'rxjs';
import GLightbox from 'glightbox';
import counterUp from 'counterup2';
import { FixturesStats, ResultService } from 'src/app/services/result.service';

@Component({
  selector: 'app-senior-national-team-history',
  imports: [],
  templateUrl: './senior-national-team-history.component.html',
  styleUrl: './senior-national-team-history.component.scss',
})
export class SeniorNationalTeamHistoryComponent implements OnInit, AfterViewInit, OnDestroy {
  resultService = inject(ResultService);
  totalMatches = signal<number>(0);
  totalWins = signal<number>(0);
  totalGoals = signal<number>(0);
  trophyWins = signal<number>(0);
  private statsLoaded = signal<boolean>(false);

  @ViewChildren('counterEl', { read: ElementRef })
  private counterEls!: QueryList<ElementRef<HTMLElement>>;
  private io?: IntersectionObserver;
  private lightbox: any;
  private statsSubscription?: Subscription;

  ngOnInit(): void {
    const emptyResponse: FixturesStats = {
      totalMatches: 0,
      totalWins: 0,
      totalGoals: 0,
      trophyWins: 0,
    };

    this.statsSubscription = this.resultService
      .buildFixturesStats()
      .pipe(catchError(() => of(emptyResponse)))
      .subscribe((response: FixturesStats) => {
        this.totalMatches.set(response.totalMatches);
        this.totalWins.set(response.totalWins);
        this.totalGoals.set(response.totalGoals);
        this.trophyWins.set(response.trophyWins);
        this.statsLoaded.set(true);
        this.animateVisibleCounters();
      });
  }

  ngAfterViewInit(): void {
    this.lightbox = GLightbox({
      selector: '[data-glightbox]',
    });

    this.io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting || !this.statsLoaded()) return;

          const el = entry.target as HTMLElement;
          this.runCounter(el);
          this.io?.unobserve(el);
        });
      },
      {
        threshold: 0.6,
      },
    );

    this.counterEls.forEach((ref) => this.io!.observe(ref.nativeElement));
    this.animateVisibleCounters();
  }

  ngOnDestroy(): void {
    this.statsSubscription?.unsubscribe();
    this.lightbox?.destroy();
    this.io?.disconnect();
  }

  private animateVisibleCounters(): void {
    if (!this.statsLoaded() || !this.io || !this.counterEls) {
      return;
    }

    this.counterEls.forEach((ref) => {
      const el = ref.nativeElement;
      const { top, bottom } = el.getBoundingClientRect();
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
      const isVisible = top < viewportHeight && bottom > 0;

      if (!isVisible) {
        return;
      }

      this.runCounter(el);
      this.io?.unobserve(el);
    });
  }

  private runCounter(el: HTMLElement): void {
    counterUp(el, {
      duration: 1200,
      delay: 16,
    });
  }
}
