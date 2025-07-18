import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { environment } from '../../environments/environment';
import { AuthService } from '../services/auth.service'; 

@Component({
  selector: 'app-comentario',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './comentario.component.html',
})
export class ComentarioComponent implements OnInit {
  @Input() idPublicacion!: number;
  comentarios: any[] = [];
  nuevoComentario: string = '';

  constructor(private http: HttpClient, public authService: AuthService ) {}

  ngOnInit() {
    this.cargarComentarios();
  }


  get tieneToken(): boolean {
    return !!localStorage.getItem('jwt');
  }


  cargarComentarios() {
    this.http.get<any[]>(environment.apiurl + 'comentarios/' + this.idPublicacion)
      .subscribe({
        next: res => this.comentarios = res,
        error: err => console.error('Error al cargar comentarios', err)
      });
  }

  agregarComentario() {
    const token = localStorage.getItem('jwt');
    if (!token || !this.nuevoComentario.trim()) return;

    const headers = new HttpHeaders({ Authorization: 'Bearer ' + token });
    const body = {
      id_publicacion: this.idPublicacion,
      contenido: this.nuevoComentario.trim()
    };

    this.http.post(environment.apiurl + 'comentarios', body, { headers })
      .subscribe({
        next: () => {
          this.nuevoComentario = '';
          this.cargarComentarios();
        },
        error: err => console.error('Error al agregar comentario', err)
      });
  }

  eliminarComentario(id: number) {
    if (!confirm('¿Seguro que querés eliminar este comentario?')) return;

    const token = localStorage.getItem('jwt');
    const headers = { Authorization: 'Bearer ' + token };

    this.http.delete(environment.apiurl + 'comentarios/' + id, { headers })
      .subscribe({
        next: () => {
          this.cargarComentarios(); // refresca los comentarios
        },
        error: err => {
          alert('No se pudo eliminar el comentario');
          console.error(err);
        }
      });
  }

}
