import { createReducer, on } from '@ngrx/store';
import { Product } from '../../models/product.model';
import * as ProductsActions from './products.actions';

export interface ProductsState {
  items: Product[];
  loading: boolean;
  error: string | null;
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export const initialState: ProductsState = {
  items: [],
  loading: false,
  error: null,
  total: 0,
  page: 1,
  limit: 20,
  totalPages: 1,
};

export const productsReducer = createReducer(
  initialState,
  on(ProductsActions.loadProducts, (state) => ({
    ...state,
    loading: true,
    error: null,
  })),
  on(ProductsActions.loadProductsSuccess, (state, { products, total, page, limit, totalPages }) => ({
    ...state,
    loading: false,
    items: products,
    total,
    page,
    limit,
    totalPages,
  })),
  on(ProductsActions.loadProductsFailure, (state, { error }) => ({
    ...state,
    loading: false,
    error,
  }))
);
