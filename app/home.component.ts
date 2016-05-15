import {Component, OnInit} from '@angular/core';
import {TranslatePipe} from 'ng2-translate/ng2-translate';
import {MDL} from './material-design-lite-upgrade-element.directive';
import {FixtureService} from './fixture.service';
import {Fixture} from './fixture';
import {FeaturesListComponent, FixturesListComponent} from './fixtures-list.component';

@Component({
  selector: 'soe-home',
  templateUrl: 'app/home.component.html',
  directives: [MDL, FixturesListComponent],
  pipes: [TranslatePipe]
})
export class HomeComponent {
}
