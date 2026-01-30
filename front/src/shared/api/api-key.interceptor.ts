import { HttpInterceptorFn } from '@angular/common/http';
import { environment } from '../../environments/environment';

export const apiKeyInterceptor: HttpInterceptorFn = (req, next) => {
  const apiKey = environment.api.apiKey;
  const updatedRequest = apiKey
    ? req.clone({
        setHeaders: {
          'X-API-KEY': apiKey,
        },
      })
    : req;

  return next(updatedRequest);
};
