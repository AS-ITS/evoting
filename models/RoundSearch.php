<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Round;

/**
 * RoundSearch represents the model behind the search form of `app\models\Round`.
 */
class RoundSearch extends Round
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'round'], 'integer'],
            [['voteID', 'name', 'insert_datetime', 'update_datetime'], 'safe'],
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($voteID, $params)
    {
        $query = Round::find();

        // add conditions that should always apply here

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $query;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'voteID' => $voteID,
            'round' => $this->round,
            'insert_datetime' => $this->insert_datetime,
            'update_datetime' => $this->update_datetime,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $query;
    }
}
