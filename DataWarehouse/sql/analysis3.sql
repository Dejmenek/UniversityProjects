select
	a.name,
	a.airport_code,
	count(distinct case when f.departure_airport_id = a.airport_id then f.flight_id end) as total_departures,
	count(distinct case when f.arrival_airport_id = a.airport_id then f.flight_id end) as total_arrivals,
	count(distinct f.flight_id) as total_traffic
from flights f
join airports a on f.departure_airport_id = a.airport_id or f.arrival_airport_id = a.airport_id
group by a.name, a.airport_code
order by total_traffic desc