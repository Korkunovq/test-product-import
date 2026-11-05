import { Routes } from '@angular/router';
import { authGuard } from './core/auth.guard';

export const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'products' },
  {
    path: 'login',
    loadComponent: () => import('./pages/login/login.component').then((m) => m.LoginComponent),
  },
  {
    path: 'import',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/import/import.component').then((m) => m.ImportComponent),
  },
  {
    path: 'products',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./pages/products/product-list.component').then((m) => m.ProductListComponent),
  },
  {
    path: 'products/:id',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./pages/products/product-detail.component').then((m) => m.ProductDetailComponent),
  },
  { path: '**', redirectTo: 'products' },
];
