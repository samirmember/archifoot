import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { HydraCollection } from './api-platform.types';

@Injectable({
  providedIn: 'root',
})
export class ApiClientService {
  private readonly baseUrl = environment.api.baseUrl.replace(/\/+$/, '');

  constructor(private readonly httpClient: HttpClient) {}

  public getCollection<T>(resource: string, params?: Record<string, string | number>): Observable<HydraCollection<T>> {
    return this.httpClient.get<HydraCollection<T>>(this.buildUrl(resource), {
      params: this.toHttpParams(params),
    });
  }

  public getItem<T>(resource: string, id: string | number): Observable<T> {
    return this.httpClient.get<T>(this.buildUrl(`${resource}/${id}`));
  }

  public post<T, B>(resource: string, body: B): Observable<T> {
    return this.httpClient.post<T>(this.buildUrl(resource), body);
  }

  public patch<T, B>(resource: string, id: string | number, body: B): Observable<T> {
    return this.httpClient.patch<T>(this.buildUrl(`${resource}/${id}`), body);
  }

  public delete(resource: string, id: string | number): Observable<void> {
    return this.httpClient.delete<void>(this.buildUrl(`${resource}/${id}`));
  }

  private buildUrl(resource: string): string {
    const normalizedResource = resource.replace(/^\/+/, '');
    return `${this.baseUrl}/${normalizedResource}`;
  }

  private toHttpParams(params?: Record<string, string | number>): HttpParams | undefined {
    if (!params) {
      return undefined;
    }

    return Object.entries(params).reduce((httpParams, [key, value]) => {
      return httpParams.set(key, String(value));
    }, new HttpParams());
  }
}
