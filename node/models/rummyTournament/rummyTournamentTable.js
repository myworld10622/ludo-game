module.exports = (sequelize, DataTypes) => {
    const RummyTournamentTable = sequelize.define("tbl_rummy_tournament_table", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        tournament_id: {
            type: DataTypes.INTEGER,
        },
        winner_id: {
            type: DataTypes.INTEGER,
            defaultValue: 0
        },
        round: {
            type: DataTypes.TINYINT,
        },
        total_deal_round: {
            type: DataTypes.TINYINT,
            defaultValue: 0
        },
        deal_round: {
            type: DataTypes.TINYINT,
            defaultValue: 0
        },
        start_seat_no: {
            type: DataTypes.TINYINT,
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
        tableName: 'tbl_rummy_tournament_table',
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
    });

    RummyTournamentTable.associate = (models) => {
        RummyTournamentTable.hasMany(models.user, { foreignKey: 'rummy_pool_table_id', sourceKey: 'id' });
        RummyTournamentTable.hasMany(models.RummyTournamentTableUser, { foreignKey: 'table_id', sourceKey: 'id', as: 'tbl_rummy_tournament_table_user' });
        RummyTournamentTable.belongsTo(models.RummyTournamentTableMaster, { foreignKey: 'tournament_id', as: 'tbl_rummy_tournament_master' });
    };

    return RummyTournamentTable;
}