import { createFeatureSelector, createSelector } from '@ngrx/store';
import { ProductsState } from './products.reducer';

export const selectProductsState = createFeatureSelector<ProductsState>('products');

export const selectAllProducts = createSelector(selectProductsState, (state) => state.items);

export const selectProductsLoading = createSelector(selectProductsState, (state) => state.loading);

export const selectTotalPages = createSelector(selectProductsState, (state) => state.totalPages);

export const selectProductsMeta = createSelector(selectProductsState, (state) => ({
  total: state.total,
  page: state.page,
  limit: state.limit,
  totalPages: state.totalPages,
  error: state.error,
}));
