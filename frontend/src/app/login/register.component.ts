import { Component } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { environment } from '../../environments/environment';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-register',
  standalone: true,
  templateUrl: './register.component.html',
  imports: [ReactiveFormsModule, CommonModule]
})
export class RegisterComponent {
  form: FormGroup;
  error = '';
  success = '';

  constructor(private fb: FormBuilder, private http: HttpClient, private router: Router) {
    this.form = this.fb.group({
      nombre: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      clave: ['', Validators.required]
    });
  }

  registrar() {
    this.http.post(environment.apiurl + 'usuarios', this.form.value).subscribe({
      next: () => {
        this.success = 'Usuario registrado correctamente. Ahora podés iniciar sesión.';
        this.router.navigate(['/login']); // redirige al login
      },
      error: () => {
        this.error = 'Ocurrió un error al registrar el usuario.';
      }
    });
  }
}