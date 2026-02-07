import { DatePipe } from '@angular/common';
import { Component, input, OnInit } from '@angular/core';
import { MatchResult } from 'src/app/services/result.service';

@Component({
  selector: 'app-result',
  imports: [DatePipe],
  templateUrl: './result.component.html',
  styleUrl: './result.component.scss',
})
export class ResultComponent implements OnInit {
  result = input.required<MatchResult>();
  iso2A: string | null | undefined;
  iso2B: string | null | undefined;

  ngOnInit(): void {
    this.iso2A = this.result().countryCodeA?.toLowerCase();
    this.iso2B = this.result().countryCodeB?.toLowerCase();
  }
}
