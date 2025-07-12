import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet],
  template: `<router-outlet></router-outlet>`,
  standalone: true
})
export class AppComponent {}


/*import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { environment } from '../environments/environment';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';

@Component({
 selector: 'app-root',
 standalone: true,
 imports: [RouterOutlet, CommonModule],
 templateUrl: './app.component.html',
 styleUrl: './app.component.css'
})
export class AppComponent {

  public environmentData: any = null;
  public apiSysinfo: any = null;
  
  constructor(private http: HttpClient)
  {
    this.environmentData =environment;
    this.http.get(environment.apiurl + 'sysinfo')
      .subscribe(
        (response: any) => {
          this.apiSysinfo = response;
        },
        (error) => {
          console.error(error);
        }
      );
  }
}*/
