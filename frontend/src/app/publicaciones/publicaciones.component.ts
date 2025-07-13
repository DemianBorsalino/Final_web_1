import { Component } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { environment } from '../../environments/environment';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-publicaciones',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './publicaciones.component.html',
})
export class PublicacionesComponent {
  formulario: FormGroup;
  publicaciones: any[] = [];

  constructor(private fb: FormBuilder, private http: HttpClient, public authService: AuthService ) {
    this.formulario = this.fb.group({
      titulo: [''],
      contenido: ['']
    });

    this.cargarPublicaciones();
  }

  cargarPublicaciones() {
    this.http.get<any[]>(environment.apiurl + 'publicaciones')
      .subscribe({
        next: (res) => this.publicaciones = res,
        error: (err) => console.error('Error al cargar publicaciones', err)
      });
  }

 agregarPublicacion() {
  const token = localStorage.getItem('jwt');
  if (!token) return;

  const headers = { Authorization: 'Bearer ' + token };
  this.http.post(environment.apiurl + 'publicaciones', this.formulario.value, { headers })
    .subscribe({
      next: () => {
        this.formulario.reset();
        this.cargarPublicaciones();
      },
      error: (err) => {
        console.error('Error al agregar publicación', err);
        alert('Error al publicar: ' + (err.error?.mensaje || err.message || 'Error desconocido'));
      }
    });
}
}