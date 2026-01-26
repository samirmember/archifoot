import { Component, AfterViewInit, Renderer2, Inject } from '@angular/core';
import { DOCUMENT } from '@angular/common';
import { RouterOutlet } from '@angular/router';

@Component({
  standalone: true,
  selector: 'app-root',
  imports: [RouterOutlet],
  templateUrl: './app.component.html',
})
export class AppComponent implements AfterViewInit {
  constructor(
    private renderer: Renderer2,
    @Inject(DOCUMENT) private document: Document,
  ) {}

  ngAfterViewInit() {
    // this.loadScript('assets/sandbox/js/plugins.js').then(() => {
    //   this.loadScript('assets/sandbox/js/theme.js').then(() => {
    //     (window as any).theme.init();
    //   });
    // });
  }

  private loadScript(src: string): Promise<void> {
    return new Promise((resolve) => {
      const script = this.renderer.createElement('script');
      script.src = src;
      script.onload = () => resolve();
      this.renderer.appendChild(this.document.head, script);
    });
  }
}
