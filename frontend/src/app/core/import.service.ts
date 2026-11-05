import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { ImportJob } from '../models/product.model';

interface ImportUploadResponse {
  job_id: number;
  status: string;
}

@Injectable({ providedIn: 'root' })
export class ImportService {
  constructor(private readonly http: HttpClient) {}

  upload(file: File): Observable<ImportUploadResponse> {
    const form = new FormData();
    form.append('file', file);

    return this.http.post<ImportUploadResponse>(`${environment.apiUrl}/import`, form);
  }

  getStatus(jobId: number): Observable<ImportJob> {
    return this.http.get<ImportJob>(`${environment.apiUrl}/import/${jobId}`);
  }
}
