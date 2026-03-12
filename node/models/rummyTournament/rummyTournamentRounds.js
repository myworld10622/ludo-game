module.exports = (sequelize, DataTypes) => {
    const RummyTournamentRounds = sequelize.define("tbl_rummy_tournament_rounds", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        tournament_id: {
            type: DataTypes.INTEGER
        },
        start_date: {
            type: DataTypes.DATEONLY,
        },
        start_time: {
            type: DataTypes.TIME,
        },
        round: {
            type: DataTypes.INTEGER
        },
        winner_user_count: {
            type: DataTypes.INTEGER,
        },
        table_players_info: {
            type: DataTypes.STRING,
        },
        deal_info: {
            type: DataTypes.STRING,
        },
        status: {
            type: DataTypes.TINYINT,
            defaultValue: 0,
            comment: "0=Pending, 1=In Progress, 2=Completed"
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
        tableName: 'tbl_rummy_tournament_rounds',
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

    // RummyTournamentRounds.associate = (models) => {
    //     RummyTournamentRounds.belongsTo(models.user, { foreignKey: 'user_id', as: 'tbl_users' });
    //     RummyTournamentRounds.belongsTo(models.RummyTournamentTable, { foreignKey: 'table_id', as: 'tbl_rummy_tournament_table' });
    // };

    return RummyTournamentRounds;
}