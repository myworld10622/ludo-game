module.exports = (sequelize, DataTypes) => {
  const TrippleFunMap = sequelize.define(
    "tbl_tripple_fun_map",
    {
      id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
      },
      tripple_fun_id: {
        type: DataTypes.INTEGER,
      },
      card: {
        type: DataTypes.STRING,
        allowNull: true,
      },
      added_date: {
        type: DataTypes.DATE,
        allowNull: false,
        defaultValue: DataTypes.NOW,
      },
    },
    {
      tableName: "tbl_tripple_fun_map",
      timestamps: true,
      createdAt: "added_date",
      updatedAt: false,
    }
  );

  return TrippleFunMap;
};
