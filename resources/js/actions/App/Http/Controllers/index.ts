import LeagueController from './LeagueController'
import LeagueTestScenariosController from './LeagueTestScenariosController'

const Controllers = {
    LeagueController: Object.assign(LeagueController, LeagueController),
    LeagueTestScenariosController: Object.assign(LeagueTestScenariosController, LeagueTestScenariosController),
}

export default Controllers