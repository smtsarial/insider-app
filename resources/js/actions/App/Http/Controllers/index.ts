import Settings from './Settings'
import LeagueController from './LeagueController'

const Controllers = {
    Settings: Object.assign(Settings, Settings),
    LeagueController: Object.assign(LeagueController, LeagueController),
}

export default Controllers