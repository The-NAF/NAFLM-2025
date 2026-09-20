var PageViewModel = function(leaguesJson) {
    var self = this;
    
    self.registrationViewModel = new RegistrationViewModel(leaguesJson);
        
    self.updatePlayerName = function(newName, teamId, playerId) {
        return $.ajax({
            type: 'POST',
            url: 'team_webservice.php',
            data: {
                'type': 'rename_player',
                'player': playerId,
                'teamId': teamId,
                'name': newName,
                'csrf_token': window.NAFLM_CSRF_TOKEN // Security: team_webservice.php now requires this (see header.php).
            }
        });
    };

    self.updatePlayerNumber = function(newNumber, teamId, playerId) {
        newNumber = parseInt(newNumber, 10);
        if(isNaN(newNumber))
            return;

        return $.ajax({
            type: 'POST',
            url: 'team_webservice.php',
            data: {
                'type': 'renumber_player',
                'player': playerId,
                'teamId': teamId,
                'number': newNumber,
                'csrf_token': window.NAFLM_CSRF_TOKEN // Security: team_webservice.php now requires this (see header.php).
            }
        });
    };
}