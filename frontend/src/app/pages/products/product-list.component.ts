import { AsyncPipe, DecimalPipe } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatTableModule } from '@angular/material/table';
import { debounceTime } from 'rxjs';
import { loadProducts } from '../../store/products/products.actions';
import {
  selectAllProducts,
  selectProductsLoading,
  selectProductsMeta,
  selectTotalPages,
} from '../../store/products/products.selectors';

@Component({
  selector: 'app-product-list',
  standalone: true,
  imports: [
    AsyncPipe,
    DecimalPipe,
    ReactiveFormsModule,
    RouterLink,
    MatTableModule,
    MatPaginatorModule,
    MatFormFieldModule,
    MatInputModule,
    MatProgressBarModule,
  ],
  templateUrl: './product-list.component.html',
  styleUrl: './product-list.component.scss',
})
export class ProductListComponent implements OnInit {
  private readonly store = inject(Store);
  private readonly fb = inject(FormBuilder);

  products$ = this.store.select(selectAllProducts);
  loading$ = this.store.select(selectProductsLoading);
  meta$ = this.store.select(selectProductsMeta);
  totalPages$ = this.store.select(selectTotalPages);

  displayedColumns = ['name', 'price', 'discount', 'actions'];

  filtersForm = this.fb.nonNullable.group({
    name: [''],
    priceMin: [''],
    priceMax: [''],
  });

  pageIndex = 0;
  pageSize = 20;

  ngOnInit(): void {
    this.load();
    this.filtersForm.valueChanges.pipe(debounceTime(400)).subscribe(() => {
      this.pageIndex = 0;
      this.load();
    });
  }

  onPage(event: PageEvent): void {
    this.pageIndex = event.pageIndex;
    this.pageSize = event.pageSize;
    this.load();
  }

  load(): void {
    const raw = this.filtersForm.getRawValue();
    this.store.dispatch(
      loadProducts({
        filters: {
          name: raw.name || undefined,
          priceMin: raw.priceMin ? Number(raw.priceMin) : undefined,
          priceMax: raw.priceMax ? Number(raw.priceMax) : undefined,
          page: this.pageIndex + 1,
          limit: this.pageSize,
        },
      })
    );
  }
}
