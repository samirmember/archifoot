import { Component, input, inject } from '@angular/core';
import { PlayerService, SeniorPlayer, SeniorPlayersResponse } from '../../services/player.service';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-players-list',
  imports: [RouterLink],
  templateUrl: './players-list.component.html',
  styleUrl: './players-list.component.scss',
})
export class PlayersListComponent {
  players = input<SeniorPlayer[]>();
  private readonly playerService = inject(PlayerService);

  getPlayerInitials(fullName: string): string {
    return fullName
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');
  }

  getPlayerSlug(fullName: string): string {
    return this.playerService.toSlug(fullName);
  }
}
