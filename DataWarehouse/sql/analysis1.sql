select
	ad.name as departure_airport_name,
	ad.airport_code as departure_airport_code,
	aa.name as arrival_airport_name,
	aa.airport_code as arrival_airport_code, count(*) as total_flights
from flights f
join airports aa on f.departure_airport_id = aa.airport_id
join airports ad on f.arrival_airport_id = ad.airport_id
join flight_statuses s on f.flight_status_id = s.status_id
where s.name != 'Canceled'
group by ad.airport_code, ad.name, aa.name, aa.airport_code
order by total_flights desc
limit 10