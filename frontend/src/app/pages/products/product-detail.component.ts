import { AsyncPipe, DecimalPipe } from '@angular/common';
import { Component, inject, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { Observable, Subscription, switchMap } from 'rxjs';
import { ImportService } from '../../core/import.service';
import { ProductService } from '../../core/product.service';
import { ImportJob, Product } from '../../models/product.model';

@Component({
  selector: 'app-product-detail',
  standalone: true,
  imports: [AsyncPipe, DecimalPipe, RouterLink, MatProgressBarModule],
  templateUrl: './product-detail.component.html',
  styleUrl: './product-detail.component.scss',
})
export class ProductDetailComponent implements OnInit, OnDestroy {
  private readonly route = inject(ActivatedRoute);
  private readonly productService = inject(ProductService);
  private readonly importService = inject(ImportService);

  product$!: Observable<Product>;
  importJob: ImportJob | null = null;
  private sub?: Subscription;

  ngOnInit(): void {
    this.product$ = this.route.paramMap.pipe(
      switchMap((params) => this.productService.getProduct(Number(params.get('id'))))
    );

    const jobId = Number(this.route.snapshot.queryParamMap.get('importJob'));
    if (jobId) {
      this.sub = this.importService.getStatus(jobId).subscribe((job) => {
        this.importJob = job;
      });
    }
  }

  ngOnDestroy(): void {
    this.sub?.unsubscribe();
  }
}
