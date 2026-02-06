import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root',
})
export class NumberService {
  public generateAllYears(): number[] {
    const startYear = 1963;
    const currentYear = new Date().getFullYear();
    const years: number[] = [];

    for (let year = startYear; year <= currentYear; year++) {
      years.push(year);
    }

    return years;
  }
}
