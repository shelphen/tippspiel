export class FixtureBet {

  constructor(public fixture_id:number,
              public home_goals:number,
              public away_goals:number,
              public valuation:number,
              public user_id:number,
              public created_at:number,
              public updated_at:number) {
  }
}