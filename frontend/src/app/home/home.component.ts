import { Component } from '@angular/core';
import { environment } from '../../environments/environment';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './home.component.html',
  styleUrl: './home.component.css'
})
export class HomeComponent {
  public environmentData: any = null;
  public apiSysinfo: any = null;

  constructor(private http: HttpClient) {
    this.environmentData = environment;
    this.http.get(environment.apiurl + 'sysinfo')
      .subscribe({
        next: (response) => this.apiSysinfo = response,
        error: (error) => console.error(error)
      });
  }
}