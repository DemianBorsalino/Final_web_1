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

  editarId: number | null = null;

  editarPublicacion(pub: any) {
    this.editarId = pub.id;
    this.formulario.patchValue({
      titulo: pub.titulo,
      contenido: pub.contenido
    });
  }
  
  
  cancelarEdicion() {
    this.editarId = null;
    this.formulario.reset();
  }

  agregarPublicacion() {
    const token = localStorage.getItem('jwt');
    if (!token) return;

    const headers = { Authorization: 'Bearer ' + token };

    if (this.editarId) {
      // Modo edición
      this.http.patch(environment.apiurl + 'publicaciones/' + this.editarId, this.formulario.value, { headers , responseType: 'json' })

        .subscribe({
          next: () => {
            this.formulario.reset();
            this.editarId = null;
            this.cargarPublicaciones();
          },
          error: (err) => {
            console.error('Error al editar publicación', err);
            alert('Error al editar: ' + (err.error?.mensaje || err.message || 'Error desconocido'));
          }
        });
    } else {
      // Modo agregar
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

  eliminarPublicacion(id: number) {
    if (!confirm('¿Estás seguro de que querés eliminar esta publicación?')) return;

    const token = localStorage.getItem('jwt');
    if (!token) return;

    const headers = { Authorization: 'Bearer ' + token };

    this.http.delete(environment.apiurl + 'publicaciones/' + id, { headers })
      .subscribe({
        next: () => this.cargarPublicaciones(),
        error: (err) => {
          console.error('Error al eliminar publicación', err);
          alert('Error al eliminar: ' + (err.error?.mensaje || err.message || 'Error desconocido'));
        }
      });
  }

}