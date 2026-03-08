import { Component, input } from '@angular/core';

@Component({
  selector: 'app-staff-card',
  standalone: true,
  imports: [],
  templateUrl: './staff-card.component.html',
  styleUrl: './staff-card.component.scss'
})
export class StaffCardComponent {
  name = input.required<string>();
  role = input.required<string>();
  iso2 = input<string | null>(null);
}
