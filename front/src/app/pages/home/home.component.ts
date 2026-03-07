import { Component, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { RouterModule } from '@angular/router';
import { ResultComponent } from 'src/app/components/result/result.component';
import { ResultService } from 'src/app/services/result.service';

@Component({
  selector: 'app-home',
  imports: [RouterModule, ResultComponent],
  templateUrl: './home.component.html',
  styleUrl: './home.component.scss',
})
export class HomeComponent {
  private readonly resultService = inject(ResultService);

  readonly latestResults = toSignal(
    this.resultService.getResults({ itemsPerPage: 3 }),
    { initialValue: [] },
  );
}
