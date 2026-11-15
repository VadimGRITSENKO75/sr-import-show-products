import { Injectable, inject } from '@angular/core';
import { Actions, createEffect, ofType } from '@ngrx/effects';
import { ProductService } from '../../services/product.service';
import { ProductsActions } from './products.actions';
import { catchError, map, of, switchMap } from 'rxjs';

@Injectable()
export class ProductsEffects {
  private actions$ = inject(Actions);
  private productService = inject(ProductService);

  loadProducts$ = createEffect(() =>
    this.actions$.pipe(
      ofType(ProductsActions.loadProducts),
      switchMap(({ page, limit, filters }) =>
        this.productService.getProducts(page, limit, filters).pipe(
          map((res) =>
            ProductsActions.loadProductsSuccess({
              items: res.items,
              total: res.total,
              page: res.page,
              limit: res.limit,
            })
          ),
          catchError((err) =>
            of(ProductsActions.loadProductsFailure({ error: err.message }))
          )
        )
      )
    )
  );
}