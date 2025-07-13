import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-mis-publicaciones',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './mis-publicaciones.component.html'
})
export class MisPublicacionesComponent {
  publicaciones: any[] = [];

  constructor(private http: HttpClient) {
    const token = localStorage.getItem('jwt');
    if (!token) return;

    const headers = { Authorization: 'Bearer ' + token };
    this.http.get<any[]>(environment.apiurl + 'mispublicaciones', { headers })
      .subscribe({
        next: (res) => this.publicaciones = res,
        error: (err) => console.error('Error al cargar publicaciones del usuario', err)
    });
  }
}