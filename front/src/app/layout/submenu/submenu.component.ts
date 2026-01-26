import { MenuItem } from 'primeng/api';
import { MenubarModule } from 'primeng/menubar';
import { NavigationEnd, Router } from '@angular/router';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-submenu',
  imports: [MenubarModule],
  templateUrl: './submenu.component.html',
  styleUrl: './submenu.component.scss',
})
export class SubmenuComponent implements OnInit {
  items: MenuItem[] = [];

  constructor(private router: Router) {
    this.items = [
      {
        label: 'Historique',
        styleClass: 'home',
        routerLink: '/equipe-nationale/senior/home',
        routerLinkActiveOptions: { exact: true },
      },
      {
        label: 'Matchs',
        styleClass: 'fixtures',
        routerLink: '/equipe-nationale/senior/matchs',
        routerLinkActiveOptions: { exact: true },
      },
      {
        label: 'Joueurs',
        styleClass: 'players',
        routerLink: '/equipe-nationale/senior/joueurs',
        routerLinkActiveOptions: { exact: true },
      },
      {
        label: 'Entraîneurs',
        styleClass: 'coachs',
        routerLink: '/equipe-nationale/senior/entraineurs',
        routerLinkActiveOptions: { exact: true },
      },
    ];
  }

  ngOnInit(): void {
    this.items[0].styleClass += ' active';

    // Ajout d'un gestionnaire pour récupérer l'item cliqué
    // this.items.forEach((item) => {
    //   item.command = (event) => {
    //     // event.originalEvent contient l'event natif, event.item l'item MenuItem
    //     const clickedItem = event.item;

    //     console.log('Menu item cliqué:', clickedItem);
    //     // Vous pouvez ici stocker l'item sélectionné ou effectuer d'autres actions

    //     // clickedItem?.styleClass = 'active';
    //   };
    // });

    this.router.events.subscribe((event) => {
      if (event instanceof NavigationEnd) {
        const lastSegment = event.url.split('/').pop();
        this.items.forEach((item) => {
          item.styleClass = item.styleClass?.replace(' active', '') || '';
        });

        const activeItem = this.items.find((item) =>
          item.routerLink?.toString().includes(lastSegment),
        );
        if (activeItem) {
          activeItem.styleClass += ' active';
        }
      }
    });
  }

  // ngAfterViewInit(): void {
  //   GLightbox({});
  //   // console.log(this.menubar);
  //   // console.log(typeof this.menubar);
  // }
}
