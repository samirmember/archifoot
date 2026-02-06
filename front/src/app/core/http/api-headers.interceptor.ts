import { HttpInterceptorFn } from '@angular/common/http';
import { environment } from '../../../../src/environments/environment';

export const apiHeadersInterceptor: HttpInterceptorFn = (req, next) => {
  const apiKey = environment.api.apiKey;

  // On prépare les headers à ajouter, sans écraser inutilement
  const setHeaders: Record<string, string> = {};

  // Force JSON (si pas déjà défini)
  if (!req.headers.has('Accept')) {
    setHeaders['Accept'] = 'application/json';
  }

  // API KEY (si définie)
  if (apiKey) {
    setHeaders['X-API-KEY'] = apiKey;
  }

  // Si rien à ajouter, on passe la requête telle quelle
  if (Object.keys(setHeaders).length === 0) {
    return next(req);
  }

  return next(
    req.clone({
      setHeaders,
    }),
  );
};
