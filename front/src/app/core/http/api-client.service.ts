import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root',
})
export class ApiClientService {
  private readonly baseUrl = environment.api.baseUrl.replace(/\/+$/, '');

  // Header commun : force JSON (et évite JSON-LD si ton backend négocie encore)
  private readonly jsonHeaders = new HttpHeaders({
    Accept: 'application/json',
  });

  constructor(private readonly httpClient: HttpClient) {}

  /**
   * GET générique : pour endpoints qui renvoient un objet (ou n'importe quel T)
   * Usage: this.apiClient.get<Competition>('/competitions/1')
   */
  public get<T>(
    resource: string,
    params?: Record<string, string | number | boolean>,
  ): Observable<T> {
    return this.httpClient.get<T>(this.buildUrl(resource), {
      params: this.toHttpParams(params),
      headers: this.jsonHeaders,
    });
  }

  /**
   * GET collection JSON pur : renvoie un tableau directement
   * Usage: this.apiClient.getCollection<Competition>('competitions', { pagination: false, 'order[name]': 'asc' })
   */
  public getCollection<T>(
    resource: string,
    params?: Record<string, string | number | boolean>,
  ): Observable<T[]> {
    return this.httpClient.get<T[]>(this.buildUrl(resource), {
      params: this.toHttpParams(params),
      headers: this.jsonHeaders,
    });
  }

  public getItem<T>(resource: string, id: string | number): Observable<T> {
    return this.httpClient.get<T>(this.buildUrl(`${resource}/${id}`), {
      headers: this.jsonHeaders,
    });
  }

  public post<T, B>(resource: string, body: B): Observable<T> {
    return this.httpClient.post<T>(this.buildUrl(resource), body, {
      headers: this.jsonHeaders,
    });
  }

  /**
   * Pour Merge Patch JSON, c'est mieux de préciser Content-Type.
   * Si tu n'utilises pas merge-patch, tu peux garder application/json.
   */
  public patch<T, B>(resource: string, id: string | number, body: B): Observable<T> {
    const headers = this.jsonHeaders.set('Content-Type', 'application/merge-patch+json');
    return this.httpClient.patch<T>(this.buildUrl(`${resource}/${id}`), body, { headers });
  }

  public delete(resource: string, id: string | number): Observable<void> {
    return this.httpClient.delete<void>(this.buildUrl(`${resource}/${id}`), {
      headers: this.jsonHeaders,
    });
  }

  private buildUrl(resource: string): string {
    const normalizedResource = resource.replace(/^\/+/, '');
    return `${this.baseUrl}/${normalizedResource}`;
  }

  private toHttpParams(params?: Record<string, string | number | boolean>): HttpParams | undefined {
    if (!params) return undefined;

    let httpParams = new HttpParams();
    for (const [key, value] of Object.entries(params)) {
      if (value === null || value === undefined) continue;
      httpParams = httpParams.set(key, String(value));
    }
    return httpParams;
  }
}
