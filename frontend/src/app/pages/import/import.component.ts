import { Component, inject, OnDestroy } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { interval, Subscription, switchMap, takeWhile, tap, startWith } from 'rxjs';
import { ImportService } from '../../core/import.service';
import { ImportJob } from '../../models/product.model';

@Component({
  selector: 'app-import',
  standalone: true,
  imports: [MatButtonModule, MatProgressBarModule],
  templateUrl: './import.component.html',
  styleUrl: './import.component.scss',
})
export class ImportComponent implements OnDestroy {
  private readonly importService = inject(ImportService);
  private pollSub?: Subscription;

  selectedFile: File | null = null;
  job: ImportJob | null = null;
  uploadError: string | null = null;

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.selectedFile = input.files?.[0] ?? null;
    this.uploadError = null;
  }

  upload(): void {
    if (!this.selectedFile) {
      return;
    }

    this.importService.upload(this.selectedFile).subscribe({
      next: (res) => {
        this.startPolling(res.job_id);
      },
      error: (err) => {
        this.uploadError = err?.error?.error ?? 'Ошибка загрузки файла';
      },
    });
  }

  private startPolling(jobId: number): void {
    this.pollSub?.unsubscribe();
    this.pollSub = interval(2000)
      .pipe(
        startWith(0),
        switchMap(() => this.importService.getStatus(jobId)),
        tap((job) => {
          this.job = job;
        }),
        takeWhile((job) => job.status === 'pending' || job.status === 'processing', true)
      )
      .subscribe();
  }

  ngOnDestroy(): void {
    this.pollSub?.unsubscribe();
  }
}
