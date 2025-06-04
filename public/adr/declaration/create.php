import React from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell, LabelList } from 'recharts';

const StatistiquesVentes = () => {
  // Données basées sur les analyses précédentes
  const ventesParEtablissement = [
    { name: 'SUD-EST', ventes: 38 },
    { name: 'ILE DE FRANCE', ventes: 23 },
    { name: 'RHONE-ALPES', ventes: 23 },
    { name: 'GILCAM', ventes: 21 },
    { name: 'EST', ventes: 14 },
    { name: 'OUEST', ventes: 12 },
    { name: 'SUD-OUEST', ventes: 4 }
  ];
  
  const ventesParCommercial = [
    { name: 'FS', ventes: 23 },
    { name: 'OL', ventes: 19 },
    { name: 'YC', ventes: 15 },
    { name: 'SH', ventes: 14 },
    { name: 'FM', ventes: 14 },
    { name: 'DAD', ventes: 7 },
    { name: 'MI', ventes: 7 },
    { name: 'HDA', ventes: 7 },
    { name: 'SDS', ventes: 6 },
    { name: 'Autres', ventes: 23 }
  ];
  
  const ventesParMois = [
    { name: '01/2024', ventes: 7 },
    { name: '02/2024', ventes: 13 },
    { name: '03/2024', ventes: 7 },
    { name: '04/2024', ventes: 17 },
    { name: '05/2024', ventes: 14 },
    { name: '06/2024', ventes: 12 },
    { name: '07/2024', ventes: 13 },
    { name: '08/2024', ventes: 8 },
    { name: '09/2024', ventes: 8 },
    { name: '10/2024', ventes: 9 },
    { name: '11/2024', ventes: 13 },
    { name: '12/2024', ventes: 14 }
  ];
  
  // Nouvelles données sur les types de produits
  const ventesParTypeProduit = [
    { name: 'GL33', ventes: 50 },
    { name: 'GL40', ventes: 48 },
    { name: 'GL403M3HSCD', ventes: 9 },
    { name: 'GL50', ventes: 7 },
    { name: 'GL403M3HV2', ventes: 6 },
    { name: 'GL5015M3HV2', ventes: 4 },
    { name: 'Autres GL', ventes: 11 }
  ];
  
  // Catégorisation par famille de produits
  const ventesParFamilleProduit = [
    { name: 'CLARIFICATEUR STANDARD', ventes: 97 },
    { name: 'CLARIFICATEUR AUTONOME', ventes: 38 }
  ];
  
  // Données cumulatives des ventes par commercial par mois
  const ventesCommercialParMois = [
    { mois: '01/2024', FS: 3, OL: 2, YC: 1, SH: 1, Autres: 0 },
    { mois: '02/2024', FS: 4, OL: 3, YC: 2, SH: 2, Autres: 2 },
    { mois: '03/2024', FS: 1, OL: 1, YC: 2, SH: 1, Autres: 2 },
    { mois: '04/2024', FS: 2, OL: 4, YC: 3, SH: 3, Autres: 5 },
    { mois: '05/2024', FS: 3, OL: 2, YC: 2, SH: 2, Autres: 5 },
    { mois: '06/2024', FS: 4, OL: 2, YC: 1, SH: 2, Autres: 3 },
    { mois: '07/2024', FS: 2, OL: 3, YC: 1, SH: 1, Autres: 6 },
    { mois: '08/2024', FS: 1, OL: 1, YC: 0, SH: 1, Autres: 5 },
    { mois: '09/2024', FS: 1, OL: 1, YC: 2, SH: 0, Autres: 4 },
    { mois: '10/2024', FS: 1, OL: 0, YC: 1, SH: 0, Autres: 7 },
    { mois: '11/2024', FS: 0, OL: 0, YC: 0, SH: 1, Autres: 12 },
    { mois: '12/2024', FS: 1, OL: 0, YC: 0, SH: 0, Autres: 13 }
  ];
  
  // Performances cumulées des commerciaux
  const performancesCumulees = [
    { commercial: 'FS', premier_trimestre: 8, deuxieme_trimestre: 9, troisieme_trimestre: 4, quatrieme_trimestre: 2, total: 23 },
    { commercial: 'OL', premier_trimestre: 6, deuxieme_trimestre: 8, troisieme_trimestre: 5, quatrieme_trimestre: 0, total: 19 },
    { commercial: 'YC', premier_trimestre: 5, deuxieme_trimestre: 6, troisieme_trimestre: 3, quatrieme_trimestre: 1, total: 15 },
    { commercial: 'SH', premier_trimestre: 4, deuxieme_trimestre: 7, troisieme_trimestre: 2, quatrieme_trimestre: 1, total: 14 },
    { commercial: 'FM', premier_trimestre: 3, deuxieme_trimestre: 5, troisieme_trimestre: 3, quatrieme_trimestre: 3, total: 14 }
  ];
  
  // Couleurs pour les graphiques circulaires
  const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D', '#FFC658'];
  
  return (
    <div className="p-4">
      <h1 className="text-2xl font-bold mb-6">Analyse et répartition vente Clarificateurs sur 2024</h1>
      
      <div className="bg-white p-4 rounded shadow mb-6">
        <h2 className="text-lg font-semibold mb-2">Nombre de Ventes par Agence</h2>
        <div className="flex flex-wrap mb-2">
          {ventesParEtablissement.map((agence, index) => (
            <div key={index} className="bg-gray-100 rounded-lg px-3 py-1 m-1 text-sm flex items-center">
              <div className="w-3 h-3 rounded-full mr-2" style={{ backgroundColor: COLORS[index % COLORS.length] }}></div>
              <span><b>{agence.name}</b>: {agence.ventes} ventes</span>
            </div>
          ))}
        </div>
        <ResponsiveContainer width="100%" height={350}>
          <BarChart data={ventesParEtablissement} layout="vertical" margin={{ top: 5, right: 30, left: 100, bottom: 5 }}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis type="number" />
            <YAxis type="category" dataKey="name" width={100} />
            <Tooltip formatter={(value) => [`${value} ventes`, ""]} />
            <Bar dataKey="ventes" name="Nombre de ventes">
              {ventesParEtablissement.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
              ))}
              <LabelList dataKey="ventes" position="right" fill="#000000" />
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </div>
      
      <div className="bg-white p-4 rounded shadow mb-6">
        <h2 className="text-lg font-semibold mb-4">Évolution des Ventes par Commercial (Top 5)</h2>
        <ResponsiveContainer width="100%" height={400}>
          <BarChart 
            data={ventesCommercialParMois} 
            margin={{ top: 20, right: 30, left: 20, bottom: 5 }}
          >
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="mois" />
            <YAxis />
            <Tooltip />
            <Legend />
            <Bar dataKey="FS" fill="#8884d8" stackId="a" name="FS">
              <LabelList dataKey="FS" position="top" content={({ x, y, width, height, value }) => {
                return value > 0 ? (
                  <text x={x + width / 2} y={y - 5} fill="#000" textAnchor="middle" fontSize={10}>
                    {value}
                  </text>
                ) : null;
              }} />
            </Bar>
            <Bar dataKey="OL" fill="#82ca9d" stackId="a" name="OL">
              <LabelList dataKey="OL" position="top" content={({ x, y, width, height, value }) => {
                return value > 0 ? (
                  <text x={x + width / 2} y={y - 5} fill="#000" textAnchor="middle" fontSize={10}>
                    {value}
                  </text>
                ) : null;
              }} />
            </Bar>
            <Bar dataKey="YC" fill="#ffc658" stackId="a" name="YC">
              <LabelList dataKey="YC" position="top" content={({ x, y, width, height, value }) => {
                return value > 0 ? (
                  <text x={x + width / 2} y={y - 5} fill="#000" textAnchor="middle" fontSize={10}>
                    {value}
                  </text>
                ) : null;
              }} />
            </Bar>
            <Bar dataKey="SH" fill="#ff8042" stackId="a" name="SH">
              <LabelList dataKey="SH" position="top" content={({ x, y, width, height, value }) => {
                return value > 0 ? (
                  <text x={x + width / 2} y={y - 5} fill="#000" textAnchor="middle" fontSize={10}>
                    {value}
                  </text>
                ) : null;
              }} />
            </Bar>
            <Bar dataKey="Autres" fill="#a4de6c" stackId="a" name="Autres commerciaux">
              <LabelList dataKey="Autres" position="top" content={({ x, y, width, height, value }) => {
                return value > 0 ? (
                  <text x={x + width / 2} y={y - 5} fill="#000" textAnchor="middle" fontSize={10}>
                    {value}
                  </text>
                ) : null;
              }} />
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </div>
      
      <div className="bg-white p-4 rounded shadow mb-6">
        <h2 className="text-lg font-semibold mb-2">Performance Cumulée par Commercial et par Trimestre</h2>
        <div className="overflow-x-auto">
          <table className="min-w-full bg-white">
            <thead className="bg-gray-100">
              <tr>
                <th className="py-2 px-4 border-b text-left">Commercial</th>
                <th className="py-2 px-4 border-b text-right">T1 2024</th>
                <th className="py-2 px-4 border-b text-right">T2 2024</th>
                <th className="py-2 px-4 border-b text-right">T3 2024</th>
                <th className="py-2 px-4 border-b text-right">T4 2024</th>
                <th className="py-2 px-4 border-b text-right">Total</th>
                <th className="py-2 px-4 border-b text-right">% du total</th>
              </tr>
            </thead>
            <tbody>
              {performancesCumulees.map((perf, index) => (
                <tr key={index}>
                  <td className="py-2 px-4 border-b font-semibold">{perf.commercial}</td>
                  <td className="py-2 px-4 border-b text-right">{perf.premier_trimestre}</td>
                  <td className="py-2 px-4 border-b text-right">{perf.deuxieme_trimestre}</td>
                  <td className="py-2 px-4 border-b text-right">{perf.troisieme_trimestre}</td>
                  <td className="py-2 px-4 border-b text-right">{perf.quatrieme_trimestre}</td>
                  <td className="py-2 px-4 border-b text-right font-bold">{perf.total}</td>
                  <td className="py-2 px-4 border-b text-right">{(perf.total / 135 * 100).toFixed(1)}%</td>
                </tr>
              ))}
            </tbody>
            <tfoot className="bg-gray-50">
              <tr>
                <td className="py-2 px-4 border-b font-semibold">Total Top 5</td>
                <td className="py-2 px-4 border-b text-right font-semibold">
                  {performancesCumulees.reduce((sum, perf) => sum + perf.premier_trimestre, 0)}
                </td>
                <td className="py-2 px-4 border-b text-right font-semibold">
                  {performancesCumulees.reduce((sum, perf) => sum + perf.deuxieme_trimestre, 0)}
                </td>
                <td className="py-2 px-4 border-b text-right font-semibold">
                  {performancesCumulees.reduce((sum, perf) => sum + perf.troisieme_trimestre, 0)}
                </td>
                <td className="py-2 px-4 border-b text-right font-semibold">
                  {performancesCumulees.reduce((sum, perf) => sum + perf.quatrieme_trimestre, 0)}
                </td>
                <td className="py-2 px-4 border-b text-right font-semibold">
                  {performancesCumulees.reduce((sum, perf) => sum + perf.total, 0)}
                </td>
                <td className="py-2 px-4 border-b text-right font-semibold">
                  {(performancesCumulees.reduce((sum, perf) => sum + perf.total, 0) / 135 * 100).toFixed(1)}%
                </td>
              </tr>
              <tr>
                <td className="py-2 px-4 border-b font-semibold">Autres commerciaux</td>
                <td className="py-2 px-4 border-b text-right">8</td>
                <td className="py-2 px-4 border-b text-right">13</td>
                <td className="py-2 px-4 border-b text-right">15</td>
                <td className="py-2 px-4 border-b text-right">32</td>
                <td className="py-2 px-4 border-b text-right font-semibold">50</td>
                <td className="py-2 px-4 border-b text-right">{(50 / 135 * 100).toFixed(1)}%</td>
              </tr>
              <tr>
                <td className="py-2 px-4 border-b font-semibold">TOTAL GÉNÉRAL</td>
                <td className="py-2 px-4 border-b text-right font-semibold">34</td>
                <td className="py-2 px-4 border-b text-right font-semibold">48</td>
                <td className="py-2 px-4 border-b text-right font-semibold">32</td>
                <td className="py-2 px-4 border-b text-right font-semibold">21</td>
                <td className="py-2 px-4 border-b text-right font-bold">135</td>
                <td className="py-2 px-4 border-b text-right font-bold">100.0%</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      
      <div className="bg-white p-4 rounded shadow mb-6">
        <h2 className="text-lg font-semibold mb-2">Nombre de Ventes par Commercial</h2>
        <div className="flex flex-wrap mb-2">
          {ventesParCommercial.slice(0, 5).map((com, index) => (
            <div key={index} className="bg-gray-100 rounded-lg px-3 py-1 m-1 text-sm flex items-center">
              <div className="w-3 h-3 rounded-full mr-2" style={{ backgroundColor: COLORS[index % COLORS.length] }}></div>
              <span><b>{com.name}</b>: {com.ventes} ventes</span>
            </div>
          ))}
          <div className="bg-gray-100 rounded-lg px-3 py-1 m-1 text-sm flex items-center">
            <div className="w-3 h-3 rounded-full mr-2" style={{ backgroundColor: '#a4de6c' }}></div>
            <span><b>Autres</b>: {ventesParCommercial.slice(5).reduce((sum, item) => sum + item.ventes, 0)} ventes</span>
          </div>
        </div>
        <ResponsiveContainer width="100%" height={400}>
          <BarChart data={ventesParCommercial} margin={{ top: 5, right: 30, left: 20, bottom: 5 }} layout="vertical">
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis type="number" />
            <YAxis dataKey="name" type="category" width={80} />
            <Tooltip />
            <Legend />
            <Bar dataKey="ventes" fill="#82ca9d" name="Nombre de ventes">
              <LabelList dataKey="ventes" position="right" fill="#000000" />
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </div>
      
      <div className="bg-white p-4 rounded shadow mb-6">
        <h2 className="text-lg font-semibold mb-2">Évolution des Ventes par Mois</h2>
        <div className="overflow-x-auto mb-3">
          <table className="min-w-full bg-white text-sm">
            <thead className="bg-gray-50">
              <tr>
                <th className="py-1 px-2 text-left">Mois</th>
                {ventesParMois.map((mois) => (
                  <th key={mois.name} className="py-1 px-2 text-center">{mois.name}</th>
                ))}
                <th className="py-1 px-2 text-center">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td className="py-1 px-2 font-semibold">Ventes</td>
                {ventesParMois.map((mois) => (
                  <td key={mois.name} className="py-1 px-2 text-center">{mois.ventes}</td>
                ))}
                <td className="py-1 px-2 text-center font-semibold">{ventesParMois.reduce((sum, mois) => sum + mois.ventes, 0)}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <ResponsiveContainer width="100%" height={300}>
          <BarChart data={ventesParMois} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis />
            <Tooltip />
            <Legend />
            <Bar dataKey="ventes" fill="#FF8042" name="Nombre de ventes">
              <LabelList dataKey="ventes" position="top" fill="#000000" />
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div className="bg-white p-4 rounded shadow">
          <h2 className="text-lg font-semibold mb-4">Ventes par Type de Produit</h2>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={ventesParTypeProduit} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="name" />
              <YAxis />
              <Tooltip />
              <Legend />
              <Bar dataKey="ventes" fill="#8884d8" name="Nombre de ventes">
                <LabelList dataKey="ventes" position="top" fill="#000000" />
              </Bar>
            </BarChart>
          </ResponsiveContainer>
        </div>
        
        <div className="bg-white p-4 rounded shadow">
          <h2 className="text-lg font-semibold mb-2">Répartition par Gamme de Produit</h2>
          <div className="flex flex-wrap mb-2">
            {ventesParFamilleProduit.map((famille, index) => (
              <div key={index} className="bg-gray-100 rounded-lg px-3 py-1 m-1 text-sm flex items-center">
                <div className="w-3 h-3 rounded-full mr-2" style={{ backgroundColor: COLORS[index % COLORS.length] }}></div>
                <span><b>{famille.name}</b>: {famille.ventes} ventes ({(famille.ventes / 135 * 100).toFixed(0)}%)</span>
              </div>
            ))}
          </div>
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={ventesParFamilleProduit}
                cx="50%"
                cy="50%"
                labelLine={true}
                outerRadius={100}
                fill="#8884d8"
                dataKey="ventes"
                nameKey="name"
                label={({ name, value, percent }) => `${name} : ${value} (${(percent * 100).toFixed(0)}%)`}
              >
                {ventesParFamilleProduit.map((entry, index) => (
                  <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip formatter={(value, name) => [`${value} ventes`, name]} />
            </PieChart>
          </ResponsiveContainer>
        </div>
      </div>
      
      <div className="bg-white p-4 rounded shadow mb-6">
        <h2 className="text-lg font-semibold mb-4">Tableau des Ventes par Type de Produit</h2>
        <div className="overflow-x-auto">
          <table className="min-w-full bg-white">
            <thead className="bg-gray-100">
              <tr>
                <th className="py-2 px-4 border-b text-left">Référence</th>
                <th className="py-2 px-4 border-b text-left">Description</th>
                <th className="py-2 px-4 border-b text-right">Nombre de Ventes</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td className="py-2 px-4 border-b">GL33</td>
                <td className="py-2 px-4 border-b">CLARIFICATEUR STANDARD GL33</td>
                <td className="py-2 px-4 border-b text-right">50</td>
              </tr>
              <tr>
                <td className="py-2 px-4 border-b">GL40</td>
                <td className="py-2 px-4 border-b">CLARIFICATEUR STANDARD GL40</td>
                <td className="py-2 px-4 border-b text-right">48</td>
              </tr>
              <tr>
                <td className="py-2 px-4 border-b">GL403M3HSCD</td>
                <td className="py-2 px-4 border-b">CLARIFICATEUR AUTONOME GL40 3M3/H</td>
                <td className="py-2 px-4 border-b text-right">9</td>
              </tr>
              <tr>
                <td className="py-2 px-4 border-b">GL50</td>
                <td className="py-2 px-4 border-b">CLARIFICATEUR INOX GL50</td>
                <td className="py-2 px-4 border-b text-right">7</td>
              </tr>
              <tr>
                <td className="py-2 px-4 border-b">GL403M3HV2</td>
                <td className="py-2 px-4 border-b">CLARIFICATEUR AUTONOME GL40 E/S 1"1/2 3M3/H</td>
                <td className="py-2 px-4 border-b text-right">6</td>
              </tr>
              <tr>
                <td className="py-2 px-4 border-b">GL5015M3HV2</td>
                <td className="py-2 px-4 border-b">CLARIFICATEUR AUTONOME GL50 E/S 2" 15M3/H</td>
                <td className="py-2 px-4 border-b text-right">4</td>
              </tr>
              <tr>
                <td className="py-2 px-4 border-b">Autres GL</td>
                <td className="py-2 px-4 border-b">Inclut: GL500, GL25, GL330, GLHS et autres modèles spécifiques</td>
                <td className="py-2 px-4 border-b text-right">11</td>
              </tr>
            </tbody>
            <tfoot className="bg-gray-50">
              <tr>
                <td className="py-2 px-4 border-b font-semibold"></td>
                <td className="py-2 px-4 border-b font-semibold">TOTAL</td>
                <td className="py-2 px-4 border-b text-right font-semibold">135</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  );
};

export default StatistiquesVentes;
<div class="form-group">
                                <label for="produit-code">Code produit Guldagil <span style="color: var(--adr-danger);">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="produit-code" 
                                       placeholder="Ex: GUL-001"
                                       list="produits-list">
                                <datalist id="produits-list">
                                    <!-- Sera rempli dynamiquement -->
                                </datalist>
                            </div>
                            
                            <div class="form-group">
                                <label for="produit-quantite">Quantité (L ou Kg) <span style="color: var(--adr-danger);">*</span></label>
                                <input type="number" 
                                       class="form-control" 
                                       id="produit-quantite" 
                                       placeholder="0.0" 
                                       step="0.1" 
                                       min="0.1">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="produit-designation">Désignation produit</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="produit-designation" 
                                       readonly
                                       placeholder="Sera rempli automatiquement">
                            </div>
                            
                            <div class="form-group">
                                <label for="produit-numero-onu">N° ONU</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="produit-numero-onu" 
                                       readonly
                                       placeholder="Sera rempli automatiquement">
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <button type="button" class="btn btn-success" onclick="addProductToExpedition()">
                                ➕ Ajouter à l'expédition
                            </button>
                        </div>
                    </div>

                    <!-- Liste des produits ajoutés -->
                    <div id="products-list-container">
                        <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📦 Produits de l'expédition</h4>
                        
                        <div id="products-empty" class="empty-state">
                            <div class="empty-state-icon">📦</div>
                            <p>Aucun produit ajouté</p>
                            <small>Ajoutez des produits ADR pour créer votre expédition</small>
                        </div>

                        <div id="products-table-container" style="display: none;">
                            <table class="products-table">
                                <thead>
                                    <tr>
                                        <th>Code produit</th>
                                        <th>Désignation</th>
                                        <th>N° ONU</th>
                                        <th>Quantité</th>
                                        <th>Points ADR</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="products-table-body">
                                    <!-- Lignes ajoutées dynamiquement -->
                                </tbody>
                                <tfoot>
                                    <tr style="background: var(--adr-light); font-weight: bold;">
                                        <td colspan="4">Total de l'expédition</td>
                                        <td id="total-points-adr">0 points</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Actions étape 2 -->
                    <div style="margin-top: 2rem; display: flex; justify-content: space-between;">
                        <button type="button" class="btn btn-secondary" onclick="backToDestinataire()">
                            ⬅️ Retour destinataire
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-next-to-validation" onclick="nextToValidation()" disabled>
                            Finaliser ➡️
                        </button>
                    </div>
                </div>

                <!-- ÉTAPE 3: Validation finale -->
                <div class="step-content" id="step-validation">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                        <div style="width: 50px; height: 50px; background: var(--adr-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            ✅
                        </div>
                        <div>
                            <h2>Étape 3 : Validation</h2>
                            <p style="color: #666; margin: 0;">Vérifiez et validez votre expédition ADR</p>
                        </div>
                    </div>

                    <!-- Récapitulatif complet -->
                    <div id="expedition-summary">
                        <!-- Sera rempli dynamiquement -->
                    </div>

                    <!-- Informations légales -->
                    <div style="background: #fff3cd; border: 1px solid var(--adr-warning); padding: 1rem; border-radius: var(--border-radius); margin: 2rem 0;">
                        <h5 style="color: #856404; margin-bottom: 0.5rem;">⚠️ Informations importantes</h5>
                        <ul style="margin: 0; color: #856404; font-size: 0.9rem;">
                            <li>Les Fiches de Données de Sécurité (FDS) sont disponibles sur <strong>QuickFDS</strong></li>
                            <li>Le transporteur doit vérifier la conformité ADR avant enlèvement</li>
                            <li>Cette déclaration engage la responsabilité de l'expéditeur</li>
                            <li>Document à conserver 5 ans minimum</li>
                        </ul>
                    </div>

                    <!-- Actions finales -->
                    <div style="margin-top: 2rem; display: flex; justify-content: space-between;">
                        <button type="button" class="btn btn-secondary" onclick="backToProducts()">
                            ⬅️ Retour produits
                        </button>
                        <div style="display: flex; gap: 1rem;">
                            <button type="button" class="btn btn-success" onclick="saveAsDraft()">
                                💾 Sauvegarder brouillon
                            </button>
                            <button type="button" class="btn btn-primary" onclick="createExpedition()">
                                🚀 Créer l'expédition
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne de droite : Étapes et quotas -->
            <div class="process-steps">
                <h3 style="margin-bottom: 1.5rem; color: var(--adr-primary);">📋 Processus</h3>
                
                <div class="step active" data-step="destinataire">
                    <div class="step-number">1</div>
                    <div>
                        <div style="font-weight: 600;">Destinataire</div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">Client & adresse livraison</div>
                    </div>
                </div>

                <div class="step disabled" data-step="products">
                    <div class="step-number">2</div>
                    <div>
                        <div style="font-weight: 600;">Produits ADR</div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">Ajout ligne par ligne</div>
                    </div>
                </div>

                <div class="step disabled" data-step="validation">
                    <div class="step-number">3</div>
                    <div>
                        <div style="font-weight: 600;">Validation</div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">Contrôle & création</div>
                    </div>
                </div>

                <!-- Quotas du jour -->
                <div class="quotas-section">
                    <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📊 Quotas ADR du jour</h4>
                    
                    <div id="quota-info" style="display: none;">
                        <div style="margin-bottom: 0.5rem;">
                            <strong id="quota-transporteur-name">Transporteur</strong>
                            <span id="quota-date" style="float: right; color: #666; font-size: 0.9rem;"></span>
                        </div>
                        
                        <div class="quota-bar">
                            <div class="quota-fill" id="quota-fill" style="width: 0%;"></div>
                        </div>
                        
                        <div class="quota-info">
                            <span id="quota-utilise">0 points</span>
                            <span id="quota-restant">1000 points</span>
                        </div>
                        
                        <div id="quota-alert" class="alert alert-danger" style="display: none; margin-top: 1rem;">
                            ⚠️ Attention : quota journalier dépassé !
                        </div>
                    </div>
                    
                    <div id="quota-placeholder" style="color: #666; text-align: center; padding: 1rem;">
                        Sélectionnez un transporteur et une date pour voir les quotas
                    </div>
                </div>

                <!-- Résumé expédition en cours -->
                <div id="expedition-progress" style="margin-top: 2rem; display: none;">
                    <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📦 Expédition en cours</h4>
                    
                    <div style="background: var(--adr-light); padding: 1rem; border-radius: var(--border-radius); font-size: 0.9rem;">
                        <div id="progress-client" style="margin-bottom: 0.5rem;"></div>
                        <div id="progress-products" style="margin-bottom: 0.5rem;"></div>
                        <div id="progress-points" style="font-weight: bold; color: var(--adr-primary);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions flottantes -->
    <div class="floating-actions">
        <button class="floating-btn" onclick="showHelp()" title="Aide">
            ❓
        </button>
        <button class="floating-btn" onclick="saveDraft()" title="Sauvegarder brouillon">
            💾
        </button>
    </div>

    <script>
        // Variables globales
        let currentStep = 'destinataire';
        let selectedClient = null;
        let expeditionProducts = [];
        let quotasData = null;
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚚 Initialisation création expédition ADR');
            initializeForm();
        });

        function initializeForm() {
            // Event listeners
            setupEventListeners();
            
            // Charger les produits disponibles
            loadAvailableProducts();
            
            // Initialiser la recherche client
            initializeClientSearch();
        }

        function setupEventListeners() {
            // Recherche client
            document.getElementById('search-client').addEventListener('input', handleClientSearch);
            
            // Changement transporteur/date
            document.getElementById('expedition-transporteur').addEventListener('change', updateQuotas);
            document.getElementById('expedition-date').addEventListener('change', updateQuotas);
            
            // Auto-complétion produits
            document.getElementById('produit-code').addEventListener('input', handleProductSearch);
            document.getElementById('produit-code').addEventListener('change', loadProductInfo);
            
            // Validation quantité
            document.getElementById('produit-quantite').addEventListener('input', updatePointsCalculation);
        }

        // ========== GESTION CLIENTS ==========
        
        function handleClientSearch() {
            const query = document.getElementById('search-client').value;
            
            if (query.length < 2) {
                hideClientSuggestions();
                return;
            }
            
            // Recherche avec délai
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => {
                searchClients(query);
            }, 300);
        }

        function searchClients(query) {
            const formData = new FormData();
            formData.append('action', 'search_clients');
            formData.append('query', query);
            
            fetch('', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayClientSuggestions(data.clients);
                } else {
                    console.error('Erreur recherche clients:', data.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }

        function displayClientSuggestions(clients) {
            const container = document.getElementById('client-suggestions');
            
            if (clients.length === 0) {
                container.innerHTML = `
                    <div class="client-suggestion" onclick="showNewClientForm()">
                        <div class="client-name">➕ Créer un nouveau client</div>
                        <div class="client-details">Aucun client trouvé - Cliquez pour créer</div>
                    </div>
                `;
            } else {
                let html = '';
                clients.forEach(client => {
                    html += `
                        <div class="client-suggestion" onclick="selectClient(${JSON.stringify(client).replace(/"/g, '&quot;')})">
                            <div class="client-name">${client.nom}</div>
                            <div class="client-details">${client.adresse_complete || ''} - ${client.code_postal} ${client.ville}</div>
                        </div>
                    `;
                });
                
                html += `
                    <div class="client-suggestion" onclick="showNewClientForm()" style="border-top: 2px solid var(--adr-primary);">
                        <div class="client-name">➕ Créer un nouveau client</div>
                        <div class="client-details">Créer un client qui n'existe pas</div>
                    </div>
                `;
                
                container.innerHTML = html;
            }
            
            container.style.display = 'block';
        }

        function hideClientSuggestions() {
            document.getElementById('client-suggestions').style.display = 'none';
        }

        function selectClient(client) {
            selectedClient = client;
            
            document.getElementById('search-client').value = client.nom;
            hideClientSuggestions();
            
            // Afficher les infos client sélectionné
            document.getElementById('selected-client-info').innerHTML = `
                <div><strong>${client.nom}</strong></div>
                <div>${client.adresse_complete || 'Adresse non renseignée'}</div>
                <div><strong>${client.code_postal} ${client.ville}</strong> (${client.pays || 'France'})</div>
                ${client.telephone ? `<div>Tél: ${client.telephone}</div>` : ''}
                ${client.email ? `<div>Email: ${client.email}</div>` : ''}
            `;
            
            document.getElementById('selected-client').style.display = 'block';
            document.getElementById('new-client-form').style.display = 'none';
            document.getElementById('btn-next-to-products').disabled = false;
            
            updateProgressInfo();
        }

        function showNewClientForm() {
            hideClientSuggestions();
            document.getElementById('new-client-form').style.display = 'block';
            document.getElementById('selected-client').style.display = 'none';
            document.getElementById('client-nom').focus();
        }

        function saveNewClient() {
            const formData = new FormData();
            formData.append('action', 'save_client');
            formData.append('nom', document.getElementById('client-nom').value);
            formData.append('adresse_complete', document.getElementById('client-adresse').value);
            formData.append('code_postal', document.getElementById('client-codepostal').value);
            formData.append('ville', document.getElementById('client-ville').value);
            formData.append('pays', document.getElementById('client-pays').value);
            formData.append('telephone', document.getElementById('client-telephone').value);
            formData.append('email', document.getElementById('client-email').value);
            
            fetch('', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    selectClient(data.client);
                    alert('✅ Client créé avec succès');
                } else {
                    alert('❌ Erreur: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('❌ Erreur lors de la création du client');
            });
        }

        function cancelNewClient() {
            document.getElementById('new-client-form').style.display = 'none';
            document.getElementById('search-client').value = '';
            document.getElementById('search-client').focus();
        }

        function changeClient() {
            selectedClient = null;
            document.getElementById('selected-client').style.display = 'none';
            document.getElementById('search-client').value = '';
            document.getElementById('search-client').focus();
            document.getElementById('btn-next-to-products').disabled = true;
            updateProgressInfo();
        }

        // ========== GESTION PRODUITS ==========
        
        function loadAvailableProducts() {
            // Pour démo - remplacer par un appel API réel
            const products = [
                { code: 'GUL-001', designation: 'Acide chlorhydrique 33%', numero_onu: 'UN1789', categorie: '8' },
                { code: 'GUL-002', designation: 'Hydroxyde de sodium 25%', numero_onu: 'UN1824', categorie: '8' },
                { code: 'GUL-003', designation: 'Peroxyde d\'hydrogène 35%', numero_onu: 'UN2014', categorie: '5.1' }
            ];
            
            const datalist = document.getElementById('produits-list');
            products.forEach(product => {
                const option = document.createElement('option');
                option.value = product.code;
                option.textContent = `${product.code} - ${product.designation}`;
                datalist.appendChild(option);
            });
        }

        function handleProductSearch() {
            const code = document.getElementById('produit-code').value;
            if (code.length >= 3) {
                loadProductInfo();
            }
        }

        function loadProductInfo() {
            const code = document.getElementById('produit-code').value;
            
            // Simulation - remplacer par API réelle
            const products = {
                'GUL-001': { designation: 'Acide chlorhydrique 33%', numero_onu: 'UN1789', points_par_litre: 1 },
                'GUL-002': { designation: 'Hydroxyde de sodium 25%', numero_onu: 'UN1824', points_par_litre: 1 },
                'GUL-003': { designation: 'Peroxyde d\'hydrogène 35%', numero_onu: 'UN2014', points_par_litre: 3 }
            };
            
            if (products[code]) {
                const product = products[code];
                document.getElementById('produit-designation').value = product.designation;
                document.getElementById('produit-numero-onu').value = product.numero_onu;
                window.currentProductPoints = product.points_par_litre;
            } else {
                document.getElementById('produit-designation').value = '';
                document.getElementById('produit-numero-onu').value = '';
                window.currentProductPoints = 0;
            }
            
            updatePointsCalculation();
        }

        function updatePointsCalculation() {
            const quantite = parseFloat(document.getElementById('produit-quantite').value) || 0;
            const points = quantite * (window.currentProductPoints || 0);
            
            // Afficher les points calculés (optionnel)
            console.log(`Quantité: ${quantite}L/Kg, Points: ${points}`);
        }

        function addProductToExpedition() {
            const code = document.getElementById('produit-code').value;
            const designation = document.getElementById('produit-designation').value;
            const numeroOnu = document.getElementById('produit-numero-onu').value;
            const quantite = parseFloat(document.getElementById('produit-quantite').value);
            
            if (!code || !quantite || quantite <= 0) {
                alert('❌ Veuillez remplir tous les champs requis');
                return;
            }
            
            const points = quantite * (window.currentProductPoints || 0);
            
            const product = {
                id: Date.now(), // ID temporaire
                code,
                designation,
                numero_onu: numeroOnu,
                quantite,
                points
            };
            
            expeditionProducts.push(product);
            updateProductsTable();
            clearProductForm();
            updateProgressInfo();
            updateQuotasWithCurrentProducts();
        }

        function updateProductsTable() {
            const empty = document.getElementById('products-empty');
            const table = document.getElementById('products-table-container');
            const tbody = document.getElementById('products-table-body');
            
            if (expeditionProducts.length === 0) {
                empty.style.display = 'block';
                table.style.display = 'none';
                document.getElementById('btn-next-to-validation').disabled = true;
                return;
            }
            
            empty.style.display = 'none';
            table.style.display = 'block';
            document.getElementById('btn-next-to-validation').disabled = false;
            
            let html = '';
            let totalPoints = 0;
            
            expeditionProducts.forEach(product => {
                totalPoints += product.points;
                html += `
                    <tr>
                        <td>
                            <input type="text" class="inline-edit" value="${product.code}" 
                                   onchange="updateProduct(${product.id}, 'code', this.value)">
                        </td>
                        <td>
                            <input type="text" class="inline-edit" value="${product.designation}" 
                                   onchange="updateProduct(${product.id}, 'designation', this.value)">
                        </td>
                        <td>${product.numero_onu}</td>
                        <td>
                            <input type="number" class="inline-edit" value="${product.quantite}" step="0.1" min="0.1"
                                   onchange="updateProductQuantite(${product.id}, this.value)">
                        </td>
                        <td><strong>${product.points.toFixed(1)}</strong></td>
                        <td>
                            <button class="btn btn-danger btn-sm" onclick="removeProduct(${product.id})" 
                                    title="Supprimer">
                                🗑️
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
            document.getElementById('total-points-adr').textContent = `${totalPoints.toFixed(1)} points`;
        }

        function updateProduct(id, field, value) {
            const product = expeditionProducts.find(p => p.id === id);
            if (product) {
                product[field] = value;
                updateProgressInfo();
            }
        }

        function updateProductQuantite(id, quantite) {
            const product = expeditionProducts.find(p => p.id === id);
            if (product) {
                product.quantite = parseFloat(quantite) || 0;
                product.points = product.quantite * (window.currentProductPoints || 1); // Approximation
                updateProductsTable();
                updateProgressInfo();
                updateQuotasWithCurrentProducts();
            }
        }

        function removeProduct(id) {
            if (confirm('❌ Supprimer ce produit de l\'expédition ?')) {
                expeditionProducts = expeditionProducts.filter(p => p.id !== id);
                updateProductsTable();
                updateProgressInfo();
                updateQuotasWithCurrentProducts();
            }
        }

        function clearProductForm() {
            document.getElementById('produit-code').value = '';
            document.getElementById('produit-designation').value = '';
            document.getElementById('produit-numero-onu').value = '';
            document.getElementById('produit-quantite').value = '';
        }

        // ========== GESTION QUOTAS ==========
        
        function updateQuotas() {
            const transporteur = document.getElementById('expedition-transporteur').value;
            const date = document.getElementById('expedition-date').value;
            
            if (!transporteur || !date) {
                document.getElementById('quota-info').style.display = 'none';
                document.getElementById('quota-placeholder').style.display = 'block';
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'get_quotas_jour');
            formData.append('transporteur', transporteur);
            formData.append('date', date);
            
            fetch('', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    quotasData = data;
                    updateQuotasDisplay();
                } else {
                    console.error('Erreur quotas:', data.error);
                }
            });
        }

        function updateQuotasDisplay() {
            if (!quotasData) return;
            
            document.getElementById('quota-placeholder').style.display = 'none';
            document.getElementById('quota-info').style.display = 'block';
            
            // Calculer les points de l'expédition actuelle
            const currentPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
            const totalPointsWithCurrent = quotasData.points_utilises + currentPoints;
            const pourcentageWithCurrent = (totalPointsWithCurrent / quotasData.quota_max) * 100;
            
            // Mettre à jour l'affichage
            const transporteurs = <?= json_encode($transporteurs) ?>;
            const transporteurCode = document.getElementById('expedition-transporteur').value;
            document.getElementById('quota-transporteur-name').textContent = transporteurs[transporteurCode] || transporteurCode;
            document.getElementById('quota-date').textContent = new Date(document.getElementById('expedition-date').value).toLocaleDateString('fr-FR');
            
            document.getElementById('quota-utilise').textContent = `${totalPointsWithCurrent.toFixed(1)} points`;
            document.getElementById('quota-restant').textContent = `${Math.max(0, quotasData.quota_max - totalPointsWithCurrent).toFixed(1)} restants`;
            
            // Barre de progression
            const fill = document.getElementById('quota-fill');
            fill.style.width = `${Math.min(100, pourcentageWithCurrent)}%`;
            
            // Alerte dépassement
            const alert = document.getElementById('quota-alert');
            if (totalPointsWithCurrent > quotasData.quota_max) {
                alert.style.display = 'block';
                fill.style.background = 'var(--adr-danger)';
            } else {
                alert.style.display = 'none';
                fill.style.background = '';
            }
        }

        function updateQuotasWithCurrentProducts() {
            if (quotasData) {
                updateQuotasDisplay();
            }
        }

        // ========== NAVIGATION ÉTAPES ==========
        
        function nextToProducts() {
            if (!selectedClient) {
                alert('❌ Veuillez sélectionner un client');
                return;
            }
            showStep('products');
        }

        function backToDestinataire() {
            showStep('destinataire');
        }

        function nextToValidation() {
            if (expeditionProducts.length === 0) {
                alert('❌ Veuillez ajouter au moins un produit');
                return;
            }
            
            generateExpeditionSummary();
            showStep('validation');
        }

        function backToProducts() {
            showStep('products');
        }

        function showStep(stepName) {
            // Masquer tous les contenus
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Afficher le contenu demandé
            document.getElementById('step-' + stepName).classList.add('active');
            
            // Mettre à jour les étapes dans la sidebar
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active', 'completed');
                if (step.dataset.step === stepName) {
                    step.classList.add('active');
                } else {
                    const stepOrder = ['destinataire', 'products', 'validation'];
                    const currentIndex = stepOrder.indexOf(stepName);
                    const stepIndex = stepOrder.indexOf(step.dataset.step);
                    
                    if (stepIndex < currentIndex) {
                        step.classList.add('completed');
                        step.classList.remove('disabled');
                    } else if (stepIndex > currentIndex) {
                        step.classList.add('disabled');
                    }
                }
            });
            
            currentStep = stepName;
        }

        function generateExpeditionSummary() {
            const container = document.getElementById('expedition-summary');
            const transporteur = document.getElementById('expedition-transporteur').value;
            const date = document.getElementById('expedition-date').value;
            const transporteurs = <?= json_encode($transporteurs) ?>;
            
            const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
            
            let html = `
                <!-- Informations générales -->
                <div style="background: var(--adr-light); padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
                    <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📋 Informations générales</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <h5>📤 Expéditeur</h5>
                            <div><strong><?= GULDAGIL_EXPEDITEUR['nom'] ?></strong></div>
                            <div style="white-space: pre-line; font-size: 0.9rem;"><?= GULDAGIL_EXPEDITEUR['adresse'] ?></div>
                            <div style="font-size: 0.9rem;">Tél: <?= GULDAGIL_EXPEDITEUR['telephone'] ?></div>
                        </div>
                        
                        <div>
                            <h5>📥 Destinataire</h5>
                            <div><strong>${selectedClient.nom}</strong></div>
                            <div style="font-size: 0.9rem;">${selectedClient.adresse_complete || ''}</div>
                            <div style="font-size: 0.9rem;"><strong>${selectedClient.code_postal} ${selectedClient.ville}</strong></div>
                            ${selectedClient.telephone ? `<div style="font-size: 0.9rem;">Tél: ${selectedClient.telephone}</div>` : ''}
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div>
                                <strong>🚚 Transporteur:</strong><br>
                                ${transporteurs[transporteur] || transporteur}
                            </div>
                            <div>
                                <strong>📅 Date d'expédition:</strong><br>
                                ${new Date(date).toLocaleDateString('fr-FR')}
                            </div>
                            <div>
                                <strong>⚠️ Total points ADR:</strong><br>
                                <span style="color: var(--adr-primary); font-weight: bold;">${totalPoints.toFixed(1)} points</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Détail des produits -->
                <div style="background: white; border: 1px solid #ddd; border-radius: var(--border-radius); overflow: hidden; margin-bottom: 2rem;">
                    <div style="background: var(--adr-primary); color: white; padding: 1rem;">
                        <h4 style="margin: 0;">⚠️ Produits dangereux (${expeditionProducts.length} référence${expeditionProducts.length > 1 ? 's' : ''})</h4>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: var(--adr-light);">
                                <tr>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd;">Code produit</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd;">Désignation officielle</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd;">N° ONU</th>
                                    <th style="padding: 0.75rem; text-align: right; border-bottom: 1px solid #ddd;">Quantité</th>
                                    <th style="padding: 0.75rem; text-align: right; border-bottom: 1px solid #ddd;">Points ADR</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            expeditionProducts.forEach(product => {
                html += `
                    <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><strong>${product.code}</strong></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">${product.designation}</td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><strong>${product.numero_onu}</strong></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee; text-align: right;">${product.quantite} L/Kg</td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee; text-align: right;"><strong>${product.points.toFixed(1)}</strong></td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                            <tfoot style="background: var(--adr-light); font-weight: bold;">
                                <tr>
                                    <td colspan="4" style="padding: 0.75rem; border-top: 2px solid var(--adr-primary);">TOTAL DE L'EXPÉDITION</td>
                                    <td style="padding: 0.75rem; border-top: 2px solid var(--adr-primary); text-align: right; color: var(--adr-primary);">${totalPoints.toFixed(1)} points</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Mentions légales et signatures -->
                <div style="background: white; border: 1px solid #ddd; border-radius: var(--border-radius); padding: 1.5rem;">
                    <h4 style="color: var(--adr-primary); margin-bottom: 1rem;">📝 Document officiel de transport</h4>
                    
                    <div style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 2rem;">
                        <p><strong>Fiches de Données de Sécurité (FDS) :</strong><br>
                        Disponibles sur la plateforme <strong>QuickFDS</strong> - Accès transporteur garanti</p>
                        
                        <p><strong>Déclaration de l'expéditeur :</strong><br>
                        Je soussigné certifie que les marchandises décrites ci-dessus sont correctement emballées, marquées, étiquetées et en état d'être transportées par route conformément aux dispositions applicables du règlement ADR.</p>
                        
                        <p><strong>Responsabilités :</strong><br>
                        - L'expéditeur certifie la conformité des marchandises aux règlements ADR<br>
                        - Le transporteur doit vérifier la conformité extérieure avant enlèvement<br>
                        - Document à conserver 5 ans minimum par toutes les parties</p>
                    </div>
                    
                    <!-- Zone signatures -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem; border-top: 1px solid #ddd; padding-top: 1.5rem;">
                        <div>
                            <h5 style="margin-bottom: 1rem;">📤 Signature expéditeur</h5>
                            <div style="border: 1px solid #ddd; height: 80px; border-radius: 4px; position: relative;">
                                <div style="position: absolute; bottom: 5px; left: 10px; font-size: 0.8rem; color: #666;">
                                    Date: ${new Date().toLocaleDateString('fr-FR')}<br>
                                    Nom: ________________________
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h5 style="margin-bottom: 1rem;">🚚 Signature transporteur</h5>
                            <div style="border: 1px solid #ddd; height: 80px; border-radius: 4px; position: relative;">
                                <div style="position: absolute; bottom: 5px; left: 10px; font-size: 0.8rem; color: #666;">
                                    Date: ____________________<br>
                                    Nom: ________________________
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.innerHTML = html;
        }

        // ========== MISE À JOUR INTERFACE ==========
        
        function updateProgressInfo() {
            const progressContainer = document.getElementById('expedition-progress');
            
            if (!selectedClient && expeditionProducts.length === 0) {
                progressContainer.style.display = 'none';
                return;
            }
            
            progressContainer.style.display = 'block';
            
            const clientInfo = selectedClient ? 
                `✅ Client: ${selectedClient.nom}` : 
                '⏳ Client: non sélectionné';
                
            const productsInfo = expeditionProducts.length > 0 ? 
                `✅ Produits: ${expeditionProducts.length} référence${expeditionProducts.length > 1 ? 's' : ''}` : 
                '⏳ Produits: aucun ajouté';
                
            const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
            const pointsInfo = totalPoints > 0 ? 
                `Total: ${totalPoints.toFixed(1)} points ADR` : 
                'Aucun point ADR';
            
            document.getElementById('progress-client').textContent = clientInfo;
            document.getElementById('progress-products').textContent = productsInfo;
            document.getElementById('progress-points').textContent = pointsInfo;
        }

        // ========== ACTIONS FINALES ==========
        
        function saveAsDraft() {
            if (!selectedClient || expeditionProducts.length === 0) {
                alert('❌ Impossible de sauvegarder : données incomplètes');
                return;
            }
            
            const draftData = {
                client: selectedClient,
                products: expeditionProducts,
                transporteur: document.getElementById('expedition-transporteur').value,
                date: document.getElementById('expedition-date').value
            };
            
            // Sauvegarder en localStorage pour démo
            localStorage.setItem('adr_expedition_draft', JSON.stringify(draftData));
            alert('💾 Brouillon sauvegardé avec succès');
        }

        function createExpedition() {
            if (!selectedClient || expeditionProducts.length === 0) {
                alert('❌ Impossible de créer l\'expédition : données incomplètes');
                return;
            }
            
            // Vérifier les quotas
            if (quotasData) {
                const totalPoints = expeditionProducts.reduce((sum, p) => sum + p.points, 0);
                const totalWithCurrent = quotasData.points_utilises + totalPoints;
                
                if (totalWithCurrent > quotasData.quota_max) {
                    if (!confirm(`⚠️ ATTENTION : Cette expédition dépasse le quota journalier de ${quotasData.quota_max} points (total: ${totalWithCurrent.toFixed(1)} points).\n\nVoulez-vous continuer malgré tout ?`)) {
                        return;
                    }
                }
            }
            
            const confirmMsg = `🚀 Créer l'expédition ADR ?\n\n` +
                `Client: ${selectedClient.nom}\n` +
                `Produits: ${expeditionProducts.length} référence(s)\n` +
                `Points ADR: ${expeditionProducts.reduce((sum, p) => sum + p.points, 0).toFixed(1)}\n` +
                `Transporteur: ${document.getElementById('expedition-transporteur').options[document.getElementById('expedition-transporteur').selectedIndex].text}\n\n` +
                `Cette action générera le document officiel pour le transporteur.`;
            
            if (confirm(confirmMsg)) {
                // Simulation création - remplacer par appel API réel
                alert('🎉 Expédition créée avec succès !\n\nN° d\'expédition: ADR-' + Date.now());
                
                // Redirection vers la liste
                setTimeout(() => {
                    window.location.href = 'list.php';
                }, 2000);
            }
        }

        function saveDraft() {
            saveAsDraft();
        }

        function showHelp() {
            const helpText = `🆘 AIDE - Création d'expédition ADR

📋 ÉTAPES :
1. Sélectionner ou créer un client destinataire
2. Ajouter les produits dangereux ligne par ligne
3. Valider et créer l'expédition

⚠️ POINTS IMPORTANTS :
• Les expéditions se font depuis Guldagil (68170 RIXHEIM) par défaut
• Les FDS sont disponibles sur QuickFDS
• Quota maximum : 1000 points ADR par jour et par transporteur
• Document à conserver 5 ans minimum

📞 SUPPORT :
Logistique : achats@guldagil.com
Technique : runser.jean.thomas@guldagil.com
Standard : 03 89 63 42 42`;

            alert(helpText);
        }

        // Initialisation de la recherche client
        function initializeClientSearch() {
            // Cacher les suggestions quand on clique ailleurs
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.client-search')) {
                    hideClientSuggestions();
                }
            });
        }

        // ========== FONCTIONS UTILITAIRES ==========
        
        function loadDraftIfExists() {
            const draft = localStorage.getItem('adr_expedition_draft');
            if (draft && confirm('📂 Un brouillon d\'expédition a été trouvé. Voulez-vous le charger ?')) {
                try {
                    const data = JSON.parse(draft);
                    
                    // Restaurer le client
                    if (data.client) {
                        selectClient(data.client);
                    }
                    
                    // Restaurer les produits
                    if (data.products) {
                        expeditionProducts = data.products;
                        updateProductsTable();
                    }
                    
                    // Restaurer transporteur et date
                    if (data.transporteur) {
                        document.getElementById('expedition-transporteur').value = data.transporteur;
                    }
                    if (data.date) {
                        document.getElementById('expedition-date').value = data.date;
                    }
                    
                    updateQuotas();
                    updateProgressInfo();
                    
                    // Aller à l'étape appropriée
                    if (data.client && data.products.length > 0) {
                        showStep('validation');
                    } else if (data.client) {
                        showStep('products');
                    }
                    
                } catch (error) {
                    console.error('Erreur chargement brouillon:', error);
                    localStorage.removeItem('adr_expedition_draft');
                }
            }
        }

        // Vérifier s'il y a un brouillon au chargement
        setTimeout(loadDraftIfExists, 1000);

        // Auto-sauvegarde toutes les 2 minutes si des données sont présentes
        setInterval(() => {
            if (selectedClient || expeditionProducts.length > 0) {
                console.log('💾 Auto-sauvegarde brouillon...');
                saveAsDraft();
            }
        }, 120000); // 2 minutes

        // Prévenir la perte de données
        window.addEventListener('beforeunload', function(e) {
            if (selectedClient || expeditionProducts.length > 0) {
                const message = 'Des données non sauvegardées seront perdues. Voulez-vous vraiment quitter ?';
                e.returnValue = message;
                return message;
            }
        });

        console.log('✅ Interface de création d\'expédition ADR initialisée');
    </script>
</body>
</html>
