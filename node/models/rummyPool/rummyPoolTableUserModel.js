// const { user, RummyPoolTable } = require("..");

module.exports = (sequelize, DataTypes) => {
    const RummyPoolTableUser = sequelize.define("tbl_rummy_pool_table_user", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        table_id: {
            type: DataTypes.INTEGER,
        },
        user_id: {
            type: DataTypes.INTEGER,
        },
        seat_position: {
            type: DataTypes.TINYINT,
        },
        total_points: {
            type: DataTypes.INTEGER,
        },
        card: {
            type: DataTypes.STRING,
        },
        card_position: {
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
        tableName: 'tbl_rummy_pool_table_user',
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
                name: 'idx_table_id',
                fields: ['table_id']
            },
            {
                name: 'idx_isDeleted',
                fields: ['isDeleted']
            }
        ]
    });

    RummyPoolTableUser.associate = (models) => {
        RummyPoolTableUser.belongsTo(models.user, { foreignKey: 'user_id', as: 'tbl_users' });
        RummyPoolTableUser.belongsTo(models.RummyPoolTable, { foreignKey: 'table_id', as: 'tbl_rummy_pool_table' });
    };

    return RummyPoolTableUser;
}