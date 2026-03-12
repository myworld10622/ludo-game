module.exports = (sequelize, DataTypes) => {
    const RummyTournamentTableMaster = sequelize.define("tbl_rummy_tournament_master", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        name: {
            type: DataTypes.STRING,
            allowNull: false
        },
        tournament_type_id: {
            type: DataTypes.INTEGER,
            allowNull: true
        },
        registration_start_date: {
            type: DataTypes.DATEONLY,
        },
        registration_start_time: {
            type: DataTypes.TIME,
        },
        start_date: {
            type: DataTypes.DATEONLY,
        },
        start_time: {
            type: DataTypes.TIME,
        },
        next_round_start_date: {
            type: DataTypes.DATEONLY,
        },
        next_round_start_time: {
            type: DataTypes.TIME,
        },
        max_entry_pass: {
            type: DataTypes.INTEGER
        },
        registration_fee: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        registration_chips: {
            type: DataTypes.BIGINT,
        },
        winning_amount: {
            type: DataTypes.FLOAT,
            allowNull: false
        },
        max_player: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        total_round: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        is_mega_tournament: {
            type: DataTypes.TINYINT,
            defaultValue: 0
        },
        is_winner_get_pass: {
            type: DataTypes.TINYINT,
            defaultValue: 0
        },
        total_pass_count: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        pass_of_tournament_id: {
            type: DataTypes.INTEGER
        },
        is_completed: {
            type: DataTypes.TINYINT,
            allowNull: false,
            defaultValue: 0,
            comment: "0=Not Completed, 1=Completed, 2=In Progress"
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        },
        updated_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        },
        isDeleted: {
            type: DataTypes.TINYINT,
            allowNull: false,
            defaultValue: 0
        }
    }, {
        tableName: 'tbl_rummy_tournament_master',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date',
        defaultScope: {
            where: {
                isDeleted: 0
            }
        },
        scopes: {
            withDeleted: {
                where: {}
            }
        },
        indexes: [
            {
                name: 'idx_isDeleted',
                fields: ['isDeleted']
            }
        ]
    });

    RummyTournamentTableMaster.associate = (models) => {
        RummyTournamentTableMaster.hasMany(models.RummyTournamentRounds, { foreignKey: 'tournament_id', sourceKey: 'id', as: 'rounds' });
        RummyTournamentTableMaster.hasMany(models.RummyTournamentPrizes, { foreignKey: 'tournament_id', sourceKey: 'id', as: 'prizes' });
        RummyTournamentTableMaster.hasMany(models.RummyTournamentTable, { foreignKey: 'tournament_id', sourceKey: 'id', as: 'tables' });
    };

    return RummyTournamentTableMaster;
}