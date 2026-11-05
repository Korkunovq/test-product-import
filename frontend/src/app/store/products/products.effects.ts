import { inject, Injectable } from '@angular/core';
import { Actions, createEffect, ofType } from '@ngrx/effects';
import { catchError, map, mergeMap, of } from 'rxjs';
import { ProductService } from '../../core/product.service';
import * as ProductsActions from './products.actions';

@Injectable()
export class ProductsEffects {
  private readonly actions$ = inject(Actions);
  private readonly productService = inject(ProductService);

  loadProducts$ = createEffect(() =>
    this.actions$.pipe(
      ofType(ProductsActions.loadProducts),
      mergeMap(({ filters }) =>
        this.productService.getProducts(filters).pipe(
          map((response) =>
            ProductsActions.loadProductsSuccess({
              products: response.data,
              total: response.meta.total,
              page: response.meta.page,
              limit: response.meta.limit,
              totalPages: response.meta.total_pages,
            })
          ),
          catchError((err: Error) =>
            of(ProductsActions.loadProductsFailure({ error: err.message ?? 'Load failed' }))
          )
        )
      )
    )
  );
}
