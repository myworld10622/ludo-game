module.exports = (sequelize, DataTypes) => {
    const RummyTournamentWinners = sequelize.define("tbl_rummy_tournament_winners", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        tournament_id: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        user_id: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        amount: {
            type: DataTypes.FLOAT,
            allowNull: false
        },
        total_points: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        round: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        position: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        is_ticket_win: {
            type: DataTypes.TINYINT,
            defaultValue: 0
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
        tableName: 'tbl_rummy_tournament_winners',
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
                name: 'idx_tournament_id',
                fields: ['tournament_id']
            },
            {
                name: 'idx_isDeleted',
                fields: ['isDeleted']
            }
        ]
    })

    RummyTournamentWinners.associate = (models) => {
        RummyTournamentWinners.belongsTo(models.RummyTournamentTableMaster, { foreignKey: 'tournament_id', as: 'tournament' });
        RummyTournamentWinners.belongsTo(models.user, { foreignKey: 'user_id', as: 'user' });
    };

    return RummyTournamentWinners;
}